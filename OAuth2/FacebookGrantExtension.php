<?php

namespace AdrienBrault\OAuth2FacebookGrantBundle\OAuth2;

use AdrienBrault\OAuth2FacebookGrantBundle\Model\FacebookGrantedUserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use FOS\OAuthServerBundle\Storage\GrantExtensionInterface;
use OAuth2\Model\IOAuth2Client;

use AdrienBrault\FacebookClient\FacebookClient;

class FacebookGrantExtension implements GrantExtensionInterface
{
    /**
     * @var UserProviderInterface
     */
    protected $userProvider;

    /**
     * @var FacebookClient
     */
    protected $facebookClient;

    public function __construct(UserProviderInterface $userProvider, FacebookClient $facebook)
    {
        $this->userProvider = $userProvider;
        $this->facebookClient = $facebook;
    }

    public function checkGrantExtension(IOAuth2Client $client, array $inputData, array $authHeaders)
    {
        if (!isset($inputData['facebook_access_token'])) {
            return false;
        }

        try {
            $fbData = $this->facebookClient->get('/me', array(
                'Authorization' => sprintf('OAuth %s', $inputData['facebook_access_token']),
            ))->send()->json();
        } catch (\Exception $e) {
            return false;
        }

        if (empty($fbData) || !isset($fbData['id'])) {
            return false;
        }

        $user = $this->userProvider->loadUserByUsername($fbData['id']);
        if (null === $user) {
            return false;
        }

        if ($user instanceof FacebookGrantedUserInterface) {
            $user->setFacebookAccessToken($inputData['facebook_access_token']);
        }

        return array(
            'data' => $user,
        );
    }
}
