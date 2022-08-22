<?php

namespace HamZone\QQAuth\Api\Controllers;

use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Grant\AbstractGrant;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\ArrayAccessorTrait;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use HamZone\QQAuth\Api\Controllers\QQResourceController;

class QQController extends AbstractProvider
{
  use ArrayAccessorTrait;

  protected $clientId;
  protected $secret;
  protected $redirect_uri;

  public $openid;
 

  const BASE_AUTH_URL = 'https://graph.qq.com';

  /**
   * Get authorization url to begin OAuth flow
   *
   * @return string
   */
  public function getBaseAuthorizationUrl()
  {
    return self::BASE_AUTH_URL . '/oauth2.0/authorize';
  }

  protected function getAuthorizationParameters(array $options)
  {
    $options['client_id'] = $this->clientId;

    if (!isset($options['redirect_uri'])) {
      $options['redirect_uri'] = $this->redirectUri;
    }

    $options += [
      'response_type' => 'code'
    ];

    if (empty($options['scope'])) {
      $options['scope'] = 'get_user_info';
    }

    if (empty($options['state'])) {
      $options['state'] = $this->getRandomState();
    }

    // Store the state as it may need to be accessed later on.
    $this->state = $options['state'];

    return $options;
  }

  /**
   * Get access token url to retrieve token
   * @param array $params
   * @return string
   */
  public function getBaseAccessTokenUrl(array $params)
  {
    return self::BASE_AUTH_URL . '/oauth2.0/token';
  }

  public function getAccessToken($grant, array $options = [])
  {
    $grant = $this->verifyGrant($grant);
    $params = [
      'client_id'     => $this->clientId,
      'client_secret' => $this->secret,
      'redirect_uri'  => $this->redirectUri,
      'grant_type' => 'authorization_code',
      'fmt' => 'json',
    ];

    $params   = $grant->prepareRequestParameters($params, $options);
    $request  = $this->getAccessTokenRequest($params);
    $response = $this->getParsedResponse($request);
    $prepared = $this->prepareAccessTokenResponse($response);

    return $this->createAccessToken($prepared, $grant);
  }

  protected function createAccessToken(array $response, AbstractGrant $grant)
  {
        return new AccessToken($response);
  }

  /**
   * Get provider url to fetch user details
   *
   * @param AccessToken $token
   * @return string
   * @throws IdentityProviderException
   */
  public function getResourceOwnerDetailsUrl(AccessToken $token)
  {
    $access_token = $token->getToken();
    $openid = $token->getValues()['openid'];

    return sprintf("%s/user/get_user_info?access_token=%s&oauth_consumer_key=%s&openid=%s", 
      self::BASE_AUTH_URL, $access_token, $this->clientId, $openid);
  }

  protected function getDefaultScopes()
  {
      return ['get_user_info'];
  }

  protected function checkResponse(ResponseInterface $response, $data)
  {
      
  }

  protected function createResourceOwner(array $response, AccessToken $token)
  {
      return new QQResourceController($response);
  }
  
}
