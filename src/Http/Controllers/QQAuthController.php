<?php

namespace HamZone\QQAuth\Http\Controllers;

use Exception;
// use NomisCZ\WeChatAuth\Http\Controllers\WXRespFactoryController;

use Flarum\Forum\Auth\Registration;
use Flarum\Http\UrlGenerator;
use Flarum\Settings\SettingsRepositoryInterface;
use NomisCZ\OAuth2\Client\Provider\WeChat;
use NomisCZ\OAuth2\Client\Provider\WeChatOffical;
use NomisCZ\OAuth2\Client\Provider\WeChatResourceOwner;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Laminas\Diactoros\Response\RedirectResponse;

class QQAuthController implements RequestHandlerInterface
{
    /**
     * @var WXRespFactoryController
     */
    protected $response;
    /**
     * @var SettingsRepositoryInterface
     */
    protected $settings;
    /**
     * @var UrlGenerator
     */
    protected $url;
    /**
     * @param WXRespFactoryController $response
     * @param SettingsRepositoryInterface $settings
     * @param UrlGenerator $url
     */
    public function __construct(WXRespFactoryController $response, SettingsRepositoryInterface $settings, UrlGenerator $url)
    {
        $this->response = $response;
        $this->settings = $settings;
        $this->url = $url;
    }
    /**
     * @param Request $request
     * @return ResponseInterface
     * @throws Exception
     */
    public function handle(Request $request): ResponseInterface
    {
        $redirectUri = $this->url->to('forum')->route('auth.qq');
        $provider = new WeChatOffical([
            'appid' => $this->settings->get('flarum-ext-auth-wechat.mp_app_id'),
            'secret' => $this->settings->get('flarum-ext-auth-wechat.mp_app_secret'),
            'redirect_uri' => $redirectUri,
        ]);

        $session = $request->getAttribute('session');
        $queryParams = $request->getQueryParams();
        $code = array_get($queryParams, 'code');

        if (!$code) {
            $authUrl = $provider->getAuthorizationUrl();
            $session->put('oauth2state', $provider->getState());
            return new RedirectResponse($authUrl . '#wechat_redirect');
            // return new RedirectResponse($authUrl . '&display=popup');
        }

        $state = array_get($queryParams, 'state');

        if (!$state || $state !== $session->get('oauth2state')) {

            $session->remove('oauth2state');
            throw new Exception('Invalid state');
        }

        $token = $provider->getAccessToken('authorization_code', compact('code'));
        /** @var WeChatResourceOwner $user */
        $user = $provider->getResourceOwner($token);

        return $this->response->make(
            'QQ',
            $user->getUnionId(),
            function (Registration $registration) use ($user) {
                $registration
                    ->suggestUsername($user->getNickname())
                    ->setPayload($user->toArray());

                if ($user->getHeadImgUrl()) {
                    $registration->provideAvatar($user->getHeadImgUrl());
                }
            }
        );
    }
}