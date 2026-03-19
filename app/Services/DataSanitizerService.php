<?php

namespace App\Services;
//classe de serviço para sanitização mais segura dos dados de entrada

class DataSanitizerService
{
  /**
   * Tipos conhecidos para sanitização
   */
  const TYPE_STRING = 'string';
  const TYPE_INTEGER = 'integer';
  const TYPE_FLOAT = 'float';
  const TYPE_EMAIL = 'email';
  const TYPE_URL = 'url';
  const TYPE_HTML = 'html';
  const TYPE_DATE = 'date';

  /**
   * Sanitiza um texto simples removendo tags HTML e caracteres especiais
   *
   * @param string|null $input Texto a ser sanitizado
   * @return string|null
   */
  public function sanitizeString(?string $input): ?string
  {
    if ($input === null) {
      return null;
    }

    // Remove tags HTML e converte caracteres especiais
    $output = strip_tags($input);
    $output = htmlspecialchars($output, ENT_QUOTES, 'UTF-8');

    return $output;
  }

  /**
   * Sanitiza um valor para garantir que seja um inteiro
   *
   * @param mixed $input Valor a ser sanitizado
   * @return int|null
   */
  public function sanitizeInteger($input): ?int
  {
    if ($input === null || $input === '') {
      return null;
    }

    return filter_var($input, FILTER_VALIDATE_INT) !== false
      ? (int) $input
      : null;
  }

  /**
   * Sanitiza um valor para garantir que seja um número de ponto flutuante
   *
   * @param mixed $input Valor a ser sanitizado
   * @return float|null
   */
  public function sanitizeFloat($input): ?float
  {
    if ($input === null || $input === '') {
      return null;
    }

    // Normaliza valor com ponto como separador decimal
    $input = str_replace(',', '.', (string) $input);

    return filter_var($input, FILTER_VALIDATE_FLOAT) !== false
      ? (float) $input
      : null;
  }

  /**
   * Sanitiza um email
   *
   * @param string|null $input Email a ser sanitizado
   * @return string|null
   */
  public function sanitizeEmail(?string $input): ?string
  {
    if ($input === null || $input === '') {
      return null;
    }

    $email = filter_var($input, FILTER_SANITIZE_EMAIL);

    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : null;
  }

  /**
   * Sanitiza uma URL
   *
   * @param string|null $input URL a ser sanitizada
   * @return string|null
   */
  public function sanitizeUrl(?string $input): ?string
  {
    if ($input === null || $input === '') {
      return null;
    }

    $url = filter_var($input, FILTER_SANITIZE_URL);

    return filter_var($url, FILTER_VALIDATE_URL) !== false ? $url : null;
  }

  /**
   * Sanitiza HTML permitindo apenas tags e atributos seguros.
   *
   * @param string|null $input HTML a ser sanitizado
   * @return string|null
   */
  public function sanitizeHtml(?string $input): ?string
  {
    if ($input === null) {
      return null;
    }

    // Passo 1: remover tags não permitidas
    $allowedTags = '<p><br><b><i><strong><em><u><ul><ol><li><h1><h2><h3><h4><h5><h6><a><hr>';
    $output = strip_tags($input, $allowedTags);

    // Passo 2: usar DOMDocument para limpar atributos perigosos
    $dom = new \DOMDocument();
    @$dom->loadHTML(
      mb_convert_encoding('<div>' . $output . '</div>', 'HTML-ENTITIES', 'UTF-8'),
      LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );

    $xpath    = new \DOMXPath($dom);
    $allNodes = $xpath->query('//*');

    if ($allNodes) {
      foreach ($allNodes as $node) {
        /** @var \DOMElement $node */
        $attributesToRemove = [];

        for ($i = 0; $i < $node->attributes->length; $i++) {
          $attr      = $node->attributes->item($i);
          $attrName  = strtolower($attr->nodeName);
          $attrValue = strtolower(preg_replace('/[\x00-\x1F\x7F\s]/u', '', $attr->nodeValue));

          // Remover qualquer event handler (onclick, onload, onmouseover...)
          if (str_starts_with($attrName, 'on')) {
            $attributesToRemove[] = $attr->nodeName;
            continue;
          }

          // Remover style, class, id (vetores de data exfiltration via CSS)
          if (in_array($attrName, ['style', 'class', 'id'])) {
            $attributesToRemove[] = $attr->nodeName;
            continue;
          }

          // Remover href/src com javascript: ou vbscript: (mesmo com encoding tricks)
          if (in_array($attrName, ['href', 'src', 'action', 'formaction'])) {
            if (str_starts_with($attrValue, 'javascript:') || str_starts_with($attrValue, 'vbscript:')) {
              $attributesToRemove[] = $attr->nodeName;
            }
          }
        }

        foreach ($attributesToRemove as $name) {
          $node->removeAttribute($name);
        }
      }
    }

    // Extrair apenas o conteúdo interno do <div> temporário
    $innerDiv = $dom->getElementsByTagName('div')->item(0);
    if (!$innerDiv) {
      return '';
    }

    $cleaned = '';
    foreach ($innerDiv->childNodes as $child) {
      $cleaned .= $dom->saveHTML($child);
    }

    return $cleaned;
  }

  /**
   * Sanitiza data no formato Y-m-d
   *
   * @param string|null $input Data a ser sanitizada
   * @return string|null
   */
  public function sanitizeDate(?string $input): ?string
  {
    if ($input === null || $input === '') {
      return null;
    }

    $date = date_create($input);
    if (!$date) {
      return null;
    }

    return date_format($date, 'Y-m-d');
  }

  /**
   * Sanitiza um array recursivamente
   *
   * @param array $input Array a ser sanitizado
   * @param array $schema Esquema com tipos para cada campo
   * @return array
   */
  public function sanitizeArray(array $input, array $schema = []): array
  {
    $output = [];

    foreach ($input as $key => $value) {
      // Se é um array aninhado, sanitiza recursivamente
      if (is_array($value)) {
        $nestedSchema = $schema[$key] ?? [];
        $output[$key] = $this->sanitizeArray($value, $nestedSchema);
        continue;
      }

      // Determinar o tipo de sanitização
      $type = $schema[$key] ?? self::TYPE_STRING;

      // Sanitizar o valor conforme o tipo
      $output[$key] = $this->sanitizeByType($value, $type);
    }

    return $output;
  }

  /**
   * Sanitiza um valor conforme o tipo especificado
   *
   * @param mixed $value Valor a ser sanitizado
   * @param string $type Tipo de sanitização
   * @return mixed
   */
  public function sanitizeByType($value, string $type)
  {
    switch ($type) {
      case self::TYPE_INTEGER:
        return $this->sanitizeInteger($value);
      case self::TYPE_FLOAT:
        return $this->sanitizeFloat($value);
      case self::TYPE_EMAIL:
        return $this->sanitizeEmail($value);
      case self::TYPE_URL:
        return $this->sanitizeUrl($value);
      case self::TYPE_HTML:
        return $this->sanitizeHtml($value);
      case self::TYPE_DATE:
        return $this->sanitizeDate($value);
      case self::TYPE_STRING:
      default:
        return $this->sanitizeString($value);
    }
  }

  /**
   * Sanitiza entrada de formulário com base em um conjunto de regras
   *
   * @param array $input Dados de entrada
   * @param array $rules Regras de sanitização
   * @return array
   */
  public function sanitizeForm(array $input, array $rules): array
  {
    $output = [];

    foreach ($rules as $field => $rule) {
      if (!isset($input[$field])) {
        continue;
      }

      $value = $input[$field];
      $output[$field] = $this->sanitizeByType($value, $rule);
    }

    return $output;
  }
}
