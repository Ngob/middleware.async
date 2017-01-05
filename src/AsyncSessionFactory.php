<?php

namespace Interop\Session\Middleware\Async;

use Interop\Session\Manager\{SessionManagerInterface as SessionManager};
use Interop\Session\Utils\ArraySession\SavableSession as Session;

class AsyncSessionFactory
{
    protected $manager = null;

    protected $session = null;

    private $dataBeforeMutation = null;

    private $hasBeenSaved = false;

    public function __construct(SessionManager $manager)
    {
        $this->sessionManager = $manager;
    }

    public function get(): Session
    {
        if (!$this->session) {
            $oldActive = $this->sessionManager->isSessionActive();
            $this->sessionManager->ensureSessionHasStart();
            $this->dataBeforeMutation = $_SESSION;

            $this->session = new Session($this->dataBeforeMutation, "");
            if (!$oldActive) {
                $this->sessionManager->close();
            }
        }
        return $this->session;
    }

    public function save()
    {
        $oldActive = $this->sessionManager->isSessionActive();
        $this->sessionManager->ensureSessionHasStart();
        foreach ($this->session as $key => $value) {
            if (!array_key_exists($key, $this->dataBeforeMutation) || $this->dataBeforeMutation[$key] != $value) {
                $_SESSION[$key] = $value;
            }
        }
        $this->sessionManager->ensureCommit();
        if ($oldActive) {
            $this->sessionManager->ensureSessionHasStart();
        }
        $this->hasBeenSaved = true;
    }

    private function __destroy()
    {
        if (!$this->hasBeenSaved) {
            $this->saveSession();
        }
    }
}