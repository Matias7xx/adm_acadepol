<?php

namespace App\Http\Controllers;

use App\Models\Matricula;
use App\Models\Curso;
use Illuminate\Http\Request;

class MatriculaController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'curso_id' => 'required|exists:cursos,id',
            'dados_adicionais' => 'nullable|array',
        ]);

        // Obtém o aluno autenticado
        $aluno = auth()->user();
        $curso = Curso::findOrFail($request->curso_id);

        // Verifica se o aluno já está matriculado
        if (Matricula::where('curso_id', $request->curso_id)->where('user_id', $aluno->id)->exists()) {
            return response()->json(['message' => 'Você já está matriculado neste curso.'], 400);

            // Verifica a capacidade do curso
        if ($curso->alunos()->count() >= $curso->capacidade_maxima) {
            return response()->json(['message' => 'O curso atingiu a capacidade máxima.'], 400);
        }
    
        // Verifica pré-requisitos
        $preRequisitos = is_string($curso->pre_requisitos) 
            ? json_decode($curso->pre_requisitos, true) ?? [] 
            : [];

        foreach ($preRequisitos as $preRequisitoId) {
    if (!$aluno->cursos()->where('curso_id', $preRequisitoId)->exists()) {
        return response()->json([
            'message' => 'Você não atende aos pré-requisitos.'
        ], 400);
    }
}
        }

        // Cria a matrícula
        Matricula::create([
            'curso_id' => $request->curso_id,
            'user_id' => $aluno->id,
            'dados_adicionais' => $request->dados_adicionais,
            'status' => 'pendente',
        ]);

        return response()->json(['message' => 'Matrícula enviada para aprovação!'], 201);
    }

    public function aprovar($id)
{
    $matricula = Matricula::findOrFail($id);

    if ($matricula->status !== 'pendente') {
        return response()->json(['message' => 'Essa matrícula já foi processada.'], 400);
    }

    // Aprovar a matrícula e vincular aluno ao curso
    $matricula->update(['status' => 'aprovada']);

    return response()->json(['message' => 'Matrícula aprovada com sucesso!']);
}

public function rejeitar($id)
{
    $matricula = Matricula::findOrFail($id);

    if ($matricula->status !== 'pendente') {
        return response()->json(['message' => 'Essa matrícula já foi processada.'], 400);
    }

    // Atualiza o status para rejeitado
    $matricula->update(['status' => 'rejeitada']);

    return response()->json(['message' => 'Matrícula rejeitada.']);
}
}