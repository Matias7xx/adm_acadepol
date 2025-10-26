<?php

namespace App\Helpers;

/**
 * Helper para manipular breadcrumbs com recursos complexos
 */
class BreadcrumbsHelper
{
  /**
   * Obtém o título adequado para um item, independentemente do tipo
   *
   * @param mixed $item O item para o qual extrair o título
   * @param string $defaultTitle Título padrão se não for possível extrair
   * @return string
   */
  public static function getTitle($item, $defaultTitle = 'Item')
  {
    // Se for um objeto
    if (is_object($item)) {
      // Tente propriedades comuns de nome
      foreach (
        ['nome', 'name', 'title', 'descricao', 'description']
        as $property
      ) {
        if (property_exists($item, $property) && !empty($item->{$property})) {
          return $item->{$property};
        }
      }

      // Se o objeto tiver um método __toString()
      if (method_exists($item, '__toString')) {
        return (string) $item;
      }
    }

    // Se for um array
    if (is_array($item)) {
      foreach (['nome', 'name', 'title', 'descricao', 'description'] as $key) {
        if (isset($item[$key]) && !empty($item[$key])) {
          return $item[$key];
        }
      }
    }

    // Se for uma string, devolve como está
    if (is_string($item) && !empty($item)) {
      return $item;
    }

    // Se for um número, converte para string
    if (is_numeric($item)) {
      return (string) $item;
    }

    // Retorna o título padrão
    return $defaultTitle;
  }
}
