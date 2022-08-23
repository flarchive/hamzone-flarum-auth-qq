<?php
namespace HamZone\QQAuth\Api\Controllers;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use Laminas\Diactoros\Response\RedirectResponse;

use Flarum\User\LoginProvider;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;

use HamZone\QQAuth\Api\Controllers\QQController;

class QQLinkController implements RequestHandlerInterface
{
    /**
     * @var LoginProvider
     */
    protected $loginProvider;
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;
    /**
     * @var UrlGenerator
     */
    protected $url;
    /**
     * @param LoginProvider $loginProvider
     * @param SettingsRepositoryInterface $settings
     * @param UrlGenerator $url
     */
    public function __construct(LoginProvider $loginProvider, SettingsRepositoryInterface $settings, UrlGenerator $url)
    {
        $this->loginProvider = $loginProvider;
        $this->settings = $settings;
        $this->url = $url;
    }
    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $actor = $request->getAttribute('actor');
        $actorLoginProviders = $actor->loginProviders()->where('provider', 'QQ')->first();

        if ($actorLoginProviders) {
            return $this->makeResponse('already_linked');
        }

        $redirectUri = $this->url->to('api')->route('auth.qq.api.link');
        app('log')->debug( $redirectUri );
        app('log')->debug( "handle" );

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
        app('log')->debug( $_SERVER['HTTP_USER_AGENT'] );

        $provider = new QQController([
            'clientId' => $this->settings->get('flarum-ext-auth-qq.app_id'),
            'secret' => $this->settings->get('flarum-ext-auth-qq.app_secret'),
            'redirect_uri' => $redirectUri,
        ]);
       

        $session = $request->getAttribute('session');
        $queryParams = $request->getQueryParams();
        $code = array_get($queryParams, 'code');

        if (!$code) {
            $authUrl = $provider->getAuthorizationUrl();
            $session->put('oauth2state', $provider->getState());
            return new RedirectResponse($authUrl);
        }

        $state = array_get($queryParams, 'state');

        if (!$state || $state !== $session->get('oauth2state')) {

            $session->remove('oauth2state');
            throw new Exception('Invalid state');
        }

        $token = $provider->getAccessToken('authorization_code', compact('code'));

        $fetchOpenId = $provider->fetchOpenId($token);
        $openId = $fetchOpenId['openid'];
        // $user = $provider->getResourceOwnerDetailsUrl($token, $openId);

        if ($this->checkLoginProvider( $openId )) {
            return $this->makeResponse('already_used');
        }
                
        $created = $actor->loginProviders()->create([
            'provider' => 'QQ',
            'identifier' => $openId
        ]);

        return $this->makeResponse($created ? 'done' : 'error');
    }

    private function makeResponse($returnCode = 'done'): HtmlResponse
    {
        $content = "<script>window.close();window.opener.app.qq.linkDone('{$returnCode}');</script>";

        return new HtmlResponse($content);
    }

    private function checkLoginProvider($identifier): bool
    {
        return $this->loginProvider->where([
            ['provider', 'QQ'],
            ['identifier', $identifier]
        ])->exists();
    }

}