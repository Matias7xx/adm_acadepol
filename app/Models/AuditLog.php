<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
  protected $fillable = [
    'modulo',
    'acao',
    'descricao',
    'model_type',
    'model_id',
    'model_label',
    'dados_anteriores',
    'dados_novos',
    'status',
    'user_id',
    'user_name',
    'user_matricula',
    'user_email',
    'ip',
    'user_agent',
    'url',
    'method',
  ];

  protected $casts = [
    'dados_anteriores' => 'array',
    'dados_novos' => 'array',
    'created_at' => 'datetime',
    'updated_at' => 'datetime',
  ];

  /**
   * Campos que nunca devem ser gravados nos logs
   */
  public const CAMPOS_SENSIVEIS = [
    'password',
    'password_confirmation',
    'senha',
    'token',
    'remember_token',
    'api_token',
    'secret',
  ];

  // Rótulos por módulo
  public const MODULOS = [
    // 'auth' => 'Autenticação',
    'usuario' => 'Usuários',
    // 'role' => 'Funções (Roles)',
    // 'permission' => 'Permissões',
    'noticia' => 'Notícias',
    'visitante' => 'Visitantes',
    'requerimento' => 'Requerimentos',
    'ocupacao' => 'Ocupações',
    'matricula' => 'Matrículas',
    'dormitorio' => 'Dormitórios',
    'director' => 'Diretores',
    'curso' => 'Cursos',
    'contato' => 'Fale Conosco',
    'certificado' => 'Certificados',
    'alojamento' => 'Alojamentos',
    // 'menu' => 'Menus',
    // 'menu_item' => 'Itens de Menu',
    'sistema' => 'Sistema',
  ];

  public const ACOES = [
    'login' => 'Login',
    'logout' => 'Logout',
    'falha_login' => 'Falha de Login',
    'acesso_negado' => 'Acesso Negado',
    'criar' => 'Criação',
    'editar' => 'Edição',
    'excluir' => 'Exclusão',
    'ativar' => 'Ativação',
    'desativar' => 'Desativação',
    'publicar' => 'Publicação',
    'arquivar' => 'Arquivamento',
    'toggle_destaque' => 'Alterar Destaque',
    'atualizar_ordem' => 'Atualizar Ordem',
    'alterar_senha' => 'Alterar Senha',
    'atribuir_role' => 'Atribuir Função',
    'gerar_pdf' => 'Gerar PDF',
    'alterar_status' => 'Alterar Status',
  ];

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Accessor: rótulo legível do módulo
   */
  public function getModuloLabelAttribute(): string
  {
    return self::MODULOS[$this->modulo] ?? ucfirst($this->modulo);
  }

  /**
   * Accessor: rótulo legível da ação
   */
  public function getAcaoLabelAttribute(): string
  {
    return self::ACOES[$this->acao] ?? ucfirst($this->acao);
  }

  /**
   * Accessor: cor de badge pelo status
   */
  public function getStatusColorAttribute(): string
  {
    return match ($this->status) {
      'success' => 'success',
      'warning' => 'warning',
      'error' => 'danger',
      default => 'info',
    };
  }

  /**
   * Scope: filtrar por módulo
   */
  public function scopeModulo($query, string $modulo)
  {
    return $query->where('modulo', $modulo);
  }

  /**
   * Scope: filtrar por usuário
   */
  public function scopeDoUsuario($query, int $userId)
  {
    return $query->where('user_id', $userId);
  }

  /**
   * Scope: filtrar por período
   */
  public function scopePeriodo($query, string $inicio, string $fim)
  {
    return $query->whereBetween('created_at', [$inicio, $fim]);
  }

  /**
   * Remove campos sensíveis de um array de dados
   */
  public static function sanitizarDados(array $dados): array
  {
    return collect($dados)
      ->except(self::CAMPOS_SENSIVEIS)
      ->filter(fn($v) => !is_null($v) && $v !== '')
      ->toArray();
  }
}
