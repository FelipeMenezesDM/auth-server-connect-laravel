<?php

namespace FelipeMenezesDM\AuthServerConnectLaravel\Middleware;

use Closure;
use FelipeMenezesDM\AuthServerConnectLaravel\Infrastructure\Attributes\AuthServerConnection;
use FelipeMenezesDM\AuthServerConnectLaravel\Infrastructure\Attributes\AuthServerValidation;
use FelipeMenezesDM\AuthServerConnectLaravel\Infrastructure\Constants\General;
use FelipeMenezesDM\AuthServerConnectLaravel\Service\AuthServerService;
use FelipeMenezesDM\AuthServerConnectLaravel\AuthServerToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use ReflectionException;
use ReflectionObject;

class AuthServerConnect
{
    private AuthServerService $authServerService;

    public function __construct()
    {
        $this->authServerService = AuthServerService::getInstance();
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response|RedirectResponse) $next
     * @return Response|RedirectResponse
     * @throws ReflectionException
     */
    public function handle(Request $request, Closure $next) : mixed
    {
        $controller = Route::getCurrentRoute()?->getController();
        $action = Route::getCurrentRoute()?->getActionMethod();

        if(!is_null($controller)) {
            $controller = new ReflectionObject($controller);
            $action = $controller->getMethod($action);

            $this->processConnection($controller);
            $this->processValidation($controller, $action, $request);
        }

        return $next($request);
    }

    private function processConnection($controller) : void
    {
        $connectionAttributes = $controller->getAttributes(AuthServerConnection::class);

        if(!empty($connectionAttributes)) {
            foreach($controller->getProperties() as $field) {
                if($field->getType()?->getName() === AuthServerToken::class && $field->isStatic()) {
                    $field->setValue($controller, $this->authServerService->authorize());
                    break;
                }
            }
        }
    }

    private function processValidation($controller, $action, $request) : void
    {
        $validationAttributes = $controller->getAttributes(AuthServerValidation::class);
        $validationAttributesAction = $action->getAttributes(AuthServerValidation::class);

        if(!empty($validationAttributes) || !empty($validationAttributesAction)) {
            $controllerScopes = array_shift($validationAttributes)?->newInstance()->getScopes();
            $actionScopes = array_shift($validationAttributesAction)?->newInstance()->getScopes();
            $scopes = array_values(array_unique(array_merge($controllerScopes??[], $actionScopes??[])));

            $this->authServerService->validate(
                $request->header(General::STR_AUTHORIZATION),
                $request->header(General::STR_CORRELATION_ID),
                $request->header(General::STR_FLOW_ID),
                $request->header(General::STR_API_KEY),
                $scopes
            );
        }
    }
}
