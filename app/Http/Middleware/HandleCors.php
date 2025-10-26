<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleCors
{
  /**
   * Handle an incoming request.
   */
  public function handle(Request $request, Closure $next): Response
  {
    $allowedOrigins = ['http://localhost:8000', 'http://localhost:8001'];

    $origin = $request->headers->get('Origin');

    $response = $next($request);

    if (in_array($origin, $allowedOrigins)) {
      $response->headers->set('Access-Control-Allow-Origin', $origin);
    }

    $response->headers->set(
      'Access-Control-Allow-Methods',
      'GET, POST, PUT, DELETE, OPTIONS',
    );
    $response->headers->set(
      'Access-Control-Allow-Headers',
      'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN, X-Inertia, X-Inertia-Version',
    );
    $response->headers->set('Access-Control-Allow-Credentials', 'true');

    // Lidar com preflight OPTIONS requests
    if ($request->isMethod('OPTIONS')) {
      $response->setStatusCode(200);
      $response->setContent('');
      $response->send();
      exit();
    }

    return $response;
  }
}
