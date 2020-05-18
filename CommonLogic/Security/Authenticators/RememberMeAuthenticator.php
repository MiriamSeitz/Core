<?php
namespace exface\Core\CommonLogic\Security\Authenticators;

use exface\Core\Interfaces\Security\AuthenticationTokenInterface;
use exface\Core\CommonLogic\Security\AuthenticationToken\RememberMeAuthToken;
use exface\Core\Exceptions\Security\AuthenticationFailedError;
use exface\Core\DataTypes\EncryptedDataType;
use exface\Core\Interfaces\WorkbenchInterface;
use exface\Core\Events\Security\OnAuthenticatedEvent;
use exface\Core\Interfaces\Security\AuthenticationProviderInterface;
use exface\Core\Interfaces\Security\AuthenticatorInterface;

/**
 * 
 * 
 * @author Andrej Kabachnik
 *
 */
class RememberMeAuthenticator extends AbstractAuthenticator
{    
    CONST SESSION_DATA_DELIMITER = ':';
    
    /**
     *
     * @param WorkbenchInterface $workbench
     */
    public function __construct(WorkbenchInterface $workbench)
    {
        parent::__construct($workbench);
        $workbench->eventManager()->addListener(OnAuthenticatedEvent::getEventName(), [$this, 'handleOnAuthenticated']);
    }
    
    /**
     * 
     * @param OnAuthenticatedEvent $event
     */
    public function handleOnAuthenticated(OnAuthenticatedEvent $event)
    {
        if ($event->getAuthenticationProvider() instanceof RememberMeAuthenticator) {
            return;
        }
        $this->saveSessionData($event->getToken(), $event->getAuthenticationProvider());
        return;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Security\AuthenticationProviderInterface::authenticate()
     */
    public function authenticate(AuthenticationTokenInterface $token) : AuthenticationTokenInterface
    {
        if (! ($token instanceof RememberMeAuthToken)) {
            throw new AuthenticationFailedError($this, 'Invalid token type!');
        }
        
        $sessionData = $this->getSessionData();
        
        $sessionUserName = $sessionData['username'];
        
        if ($token->getUsername() === null && $sessionUserName !== null) {
            $token = new RememberMeAuthToken($sessionUserName, $token->getFacade());
        } elseif ($token->getUsername() === null || $token->getUsername() !== $sessionUserName) {
            throw new AuthenticationFailedError($this, 'Cannot authenticate user "' . $token->getUsername() . '" via remember-me.');
        }
        $user = $this->getWorkbench()->getSecurity()->getUser($token);
        if ($user->hasModel() === false) {
            //return new AnonymousAuthToken($this->getWorkbench());
            throw new AuthenticationFailedError($this, 'Cannot authenticate user "' . $token->getUsername() . '" via remember-me. User does not exist!');
        }
        if  ($user->isDisabled()) {
            throw new AuthenticationFailedError($this, 'Cannot authenticate user "' . $token->getUsername() . '" via remember-me. User is disabled!');
        }        
        if (hash_equals($this->generateSessionDataHash($user->getUsername(), $sessionData['expires'], $user->getPassword()), $sessionData['hash']) === false) {
            throw new AuthenticationFailedError($this, 'Cannot authenticate user "' . $token->getUsername() . '" via remember-me. Hash is invalid!');
        }
        if ($sessionData['expires'] < time()) {
            throw new AuthenticationFailedError($this, 'Cannot authenticate user "' . $token->getUsername() . '" via remember-me. Session login time expired!');
        }
        return $token;
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Security\SecurityManagerInterface::isAuthenticated()
     */
    public function isAuthenticated(AuthenticationTokenInterface $token) : bool
    {
        return $token->getUsername() !== $this->getUsernameFromSession();
    }
    
    /**
     *
     * {@inheritDoc}
     * @see \exface\Core\Interfaces\Security\AuthenticatorInterface::isSupported()
     */
    public function isSupported(AuthenticationTokenInterface $token) : bool
    {
        return $token instanceof RememberMeAuthToken;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see \exface\Core\CommonLogic\Security\Authenticators\AbstractAuthenticator::getNameDefault()
     */
    protected function getNameDefault() : string
    {
        return 'Remember me';
    }
    
    protected function saveSessionData(AuthenticationTokenInterface $token, AuthenticationProviderInterface $provider = null) : RememberMeAuthenticator
    {
        if ($token->isAnonymous()) {
            $this->getWorkbench()->getContext()->getScopeSession()->setSessionUserData(NULL);
            return $this;
        }
        $this->getWorkbench()->getContext()->getScopeSession()->setSessionUserData($this->createSessionDataString($token, $provider));
        return $this;        
    }
    
    protected function getSessionData() : ?array
    {
        $dataString = $this->getWorkbench()->getContext()->getScopeSession()->getSessionUserData();
        if ($dataString === null) {
            return $dataString;
        }
        $dataString = EncryptedDataType::decrypt($this->getWorkbench(), $dataString);
        $data = json_decode($dataString, true);
        return $data;
    }
    
    /**
     * 
     * @param AuthenticationTokenInterface $token
     * @return string
     */
    protected function createSessionDataString(AuthenticationTokenInterface $token, AuthenticationProviderInterface $provider) : string
    {
        $data = [];
        $oldSessionData = $this->getSessionData();
        if ($oldSessionData !== null && $oldSessionData['username'] === $token->getUsername()) {
            $expires = $oldSessionData['expires'];
        } else {
            $lifetime = $this->getLifetime();
            if ($provider instanceof AuthenticatorInterface && $provider->getLifetime() !== null) {
                $lifetime = $provider->getLifetime();
            }
            $expires = time() + $lifetime;
        }
        $user = $this->getWorkbench()->getSecurity()->getUser($token);
        $data['username'] = $user->getUsername();
        $data['expires'] = $expires;
        $data['hash'] = $this->generateSessionDataHash($user->getUsername(), $expires, $user->getPassword());
        $string = json_encode($data);
        $string = EncryptedDataType::encrypt($this->getWorkbench(), $string);
        return $string;
    }
    
    /**
     * 
     * @param string $username
     * @param int $expires
     * @param string $password
     * @return string
     */
    protected function generateSessionDataHash (string $username, int $expires, string $password = null) : string
    {
        if ($password === null) {
            $password = '';
        }
        return hash_hmac('sha256', $username . self::SESSION_DATA_DELIMITER . $expires . self::SESSION_DATA_DELIMITER . $password, $this->getSecret());
    }
    
    /**
     * 
     * @return string
     */
    protected function getSecret() : string
    {
        return $this->getWorkbench()->getSecret();
    }
    
    public function getLifetime() : ?int
    {
        if ($this->lifetime === null) {
            return 604800;
        }
        return $this->lifetime;
    }
}