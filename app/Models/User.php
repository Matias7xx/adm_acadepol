<?php

namespace App\Models;

use BalajiDharma\LaravelMenu\Traits\LaravelCategories;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, HasRoles, Notifiable, LaravelCategories;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'matricula',
        'cpf',
        'cargo',
        'orgao',
        'lotacao',
        'telefone',
        'data_nascimento',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'data_nascimento' => 'date',
    ];

    /**
     * Formatar CPF para exibição
     */
    public function getFormattedCpfAttribute()
    {
        if (empty($this->cpf)) {
            return '';
        }
        
        // Remove caracteres não numéricos
        $cpf = preg_replace('/[^0-9]/', '', $this->cpf);
        
        // Formata o CPF: XXX.XXX.XXX-XX
        if (strlen($cpf) === 11) {
            return substr($cpf, 0, 3) . '.' . 
                   substr($cpf, 3, 3) . '.' . 
                   substr($cpf, 6, 3) . '-' . 
                   substr($cpf, 9, 2);
        }
        
        return $this->cpf;
    }

    /**
     * Formatar telefone para exibição
     */
    public function getFormattedTelefoneAttribute()
    {
        if (empty($this->telefone)) {
            return '';
        }
        
        // Remove caracteres não numéricos
        $telefone = preg_replace('/[^0-9]/', '', $this->telefone);
        
        // Formata telefone: (XX) XXXXX-XXXX
        if (strlen($telefone) === 11) {
            return '(' . substr($telefone, 0, 2) . ') ' . 
                   substr($telefone, 2, 5) . '-' . 
                   substr($telefone, 7, 4);
        }
        
        // Formata telefone: (XX) XXXX-XXXX
        if (strlen($telefone) === 10) {
            return '(' . substr($telefone, 0, 2) . ') ' . 
                   substr($telefone, 2, 4) . '-' . 
                   substr($telefone, 6, 4);
        }
        
        return $this->telefone;
    }

    /**
     * Formatar data_nascimento para exibição
     */
    public function getFormattedDataNascimentoAttribute()
    {
        return $this->data_nascimento ? $this->data_nascimento->format('d/m/Y') : '';
    }

    //puxar os cursos nos quais um usuário está matriculado
    public function cursos()
    {
        return $this->belongsToMany(Curso::class, 'matriculas', 'user_id', 'curso_id')
            ->withTimestamps();
    }
}