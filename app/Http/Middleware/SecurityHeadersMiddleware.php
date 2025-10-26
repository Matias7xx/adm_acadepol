<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
//camadas de proteção contra ataques comuns

class SecurityHeadersMiddleware
{
  /**
   * Headers de segurança a serem aplicados em todas as respostas
   */
  protected $securityHeaders = [
    // Impede que o navegador faça "MIME-sniffing"
    'X-Content-Type-Options' => 'nosniff',

    // Ativa proteção XSS no navegador
    'X-XSS-Protection' => '1; mode=block',

    // Impede que a página seja carregada em um iframe/frame/object
    'X-Frame-Options' => 'SAMEORIGIN',

    // Força conexões HTTPS para recursos
    'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',

    // Controla quais recursos o navegador pode carregar
    'Content-Security-Policy' =>
      "default-src 'self'; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'",

    // Impede vazamento de informações de referência para outros sites
    'Referrer-Policy' => 'strict-origin-when-cross-origin',

    // Controla quais recursos podem ser incorporados
    'Permissions-Policy' =>
      'camera=(), microphone=(), geolocation=(), interest-cohort=()',
  ];

  /**
   * Handle an incoming request.
   *
   * @param  \Illuminate\Http\Request  $request
   * @param  \Closure  $next
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function handle(Request $request, Closure $next): Response
  {
    $response = $next($request);

    // Aplicar headers de segurança na resposta
    $this->applySecurityHeaders($response);

    return $response;
  }

  /**
   * Aplica os headers de segurança na resposta HTTP
   *
   * @param \Symfony\Component\HttpFoundation\Response $response
   * @return void
   */
  protected function applySecurityHeaders(Response $response): void
  {
    foreach ($this->securityHeaders as $header => $value) {
      $response->headers->set($header, $value);
    }

    // Remove o header X-Powered-By
    $response->headers->remove('X-Powered-By');

    // Remove informações de servidor
    $response->headers->remove('Server');
  }
}
