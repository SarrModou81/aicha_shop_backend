<?php

namespace App\Http\Middleware;

use App\Models\SecurityLog;
use Closure;
use Illuminate\Http\Request;

class LogSecurity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Actions sensibles à logger
        $sensitiveActions = [
            'POST' => ['login', 'register', 'password', 'payment', 'refund'],
            'PUT' => ['password', 'status', 'role', 'permission'],
            'DELETE' => ['user', 'product', 'order', 'category']
        ];

        $method = $request->method();
        $path = $request->path();

        // Vérifier si l'action doit être loggée
        $shouldLog = false;
        if (isset($sensitiveActions[$method])) {
            foreach ($sensitiveActions[$method] as $keyword) {
                if (str_contains($path, $keyword)) {
                    $shouldLog = true;
                    break;
                }
            }
        }

        // Exécuter la requête
        $response = $next($request);

        // Logger après la requête si nécessaire
        if ($shouldLog && auth()->check()) {
            $level = 'info';

            // Déterminer le niveau selon le code de réponse
            if ($response->getStatusCode() >= 400) {
                $level = $response->getStatusCode() >= 500 ? 'danger' : 'warning';
            }

            SecurityLog::logAction(
                auth()->id(),
                $method . '_' . str_replace('/', '_', $path),
                "Requête: {$method} {$path} - Status: {$response->getStatusCode()}",
                $level,
                [
                    'method' => $method,
                    'path' => $path,
                    'status_code' => $response->getStatusCode(),
                    'input' => $request->except(['password', 'password_confirmation', 'card_cvv', 'card_number'])
                ]
            );
        }

        return $response;
    }
}
