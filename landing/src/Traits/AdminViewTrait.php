<?php

namespace App\Traits;

use Psr\Http\Message\ResponseInterface as Response;

/**
 * Trait que provee la propiedad $view inicializada desde el contenedor
 * y un helper render() para simplificar el renderizado de templates.
 */
trait AdminViewTrait
{
    protected $view;

    public function __construct(\Psr\Container\ContainerInterface $container)
    {
        $this->view = $container->get('view');
    }

    /**
     * Renderiza un template Blade y escribe el resultado en la respuesta.
     */
    protected function render(Response $response, string $template, array $data = []): Response
    {
        $html = $this->view->make($template, $data)->render();
        $response->getBody()->write($html);
        return $response;
    }
}
