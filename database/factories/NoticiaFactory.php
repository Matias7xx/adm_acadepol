<?php

namespace Database\Factories;

use App\Models\Noticia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Noticia>
 */
class NoticiaFactory extends Factory
{
    protected $model = Noticia::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = ['rascunho', 'publicado', 'arquivado'];
        
        $titulosExemplo = [
            'ACADEPOL inaugura novo laboratório de tecnologia policial',
            'Curso de especialização em investigação criminal tem início na próxima semana',
            'Formatura de novos agentes marca crescimento da corporação',
            'ACADEPOL recebe equipamentos modernos para treinamento',
            'Parceria com universidades fortalece ensino policial',
            'Novo programa de capacitação em direitos humanos',
            'ACADEPOL promove workshop sobre tecnologia e segurança',
            'Intercâmbio internacional amplia conhecimentos dos instrutores',
            'Centro de simulação recebe atualizações tecnológicas',
            'ACADEPOL celebra 25 anos de excelência em educação policial',
            'Programa de educação continuada alcança marca de 1000 participantes',
            'Nova metodologia de ensino é implementada nos cursos básicos',
            'ACADEPOL sedia congresso nacional de educação policial',
            'Biblioteca digital da ACADEPOL é expandida com novos recursos',
            'Projeto de sustentabilidade é implementado no campus da ACADEPOL'
        ];

        $conteudosExemplo = [
            $this->gerarConteudoInauguracao(),
            $this->gerarConteudoCurso(),
            $this->gerarConteudoFormatura(),
            $this->gerarConteudoEquipamentos(),
            $this->gerarConteudoParceria(),
            $this->gerarConteudoPrograma(),
            $this->gerarConteudoWorkshop(),
            $this->gerarConteudoIntercambio(),
            $this->gerarConteudoAtualizacao(),
            $this->gerarConteudoComemoracao()
        ];

        return [
            'titulo' => fake()->randomElement($titulosExemplo),
            'descricao_curta' => fake()->text(200),
            'conteudo' => fake()->randomElement($conteudosExemplo),
            'imagem' => null, // Será definido em states específicos se necessário
            'destaque' => fake()->boolean(25), // 25% chance de ser destaque
            'data_publicacao' => fake()->dateTimeBetween('-2 months', '+1 week'),
            'status' => fake()->randomElement($status),
            'visualizacoes' => fake()->numberBetween(0, 5000),
        ];
    }

    /**
     * Notícia publicada
     */
    public function publicada(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'publicado',
            'data_publicacao' => fake()->dateTimeBetween('-2 months', 'now'),
        ]);
    }

    /**
     * Notícia em rascunho
     */
    public function rascunho(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rascunho',
            'data_publicacao' => fake()->dateTimeBetween('now', '+1 month'),
        ]);
    }

    /**
     * Notícia arquivada
     */
    public function arquivada(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'arquivado',
            'data_publicacao' => fake()->dateTimeBetween('-1 year', '-3 months'),
        ]);
    }

    /**
     * Notícia em destaque
     */
    public function destaque(): static
    {
        return $this->state(fn (array $attributes) => [
            'destaque' => true,
            'status' => 'publicado',
            'data_publicacao' => fake()->dateTimeBetween('-1 month', 'now'),
            'visualizacoes' => fake()->numberBetween(500, 5000),
        ]);
    }

    /**
     * Notícia sem destaque
     */
    public function semDestaque(): static
    {
        return $this->state(fn (array $attributes) => [
            'destaque' => false,
        ]);
    }

    /**
     * Notícia popular (muitas visualizações)
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'visualizacoes' => fake()->numberBetween(2000, 10000),
            'status' => 'publicado',
            'data_publicacao' => fake()->dateTimeBetween('-3 months', '-1 week'),
        ]);
    }

    /**
     * Notícia recente
     */
    public function recente(): static
    {
        return $this->state(fn (array $attributes) => [
            'data_publicacao' => fake()->dateTimeBetween('-1 week', 'now'),
            'status' => 'publicado',
        ]);
    }

    /**
     * Gera conteúdo sobre inauguração
     */
    private function gerarConteudoInauguracao(): string
    {
        return '<p>A Academia de Polícia Civil (ACADEPOL) inaugura hoje suas novas instalações, marcando um importante avanço na educação e capacitação de profissionais de segurança pública.</p>
        
        <p>O evento contou com a presença de autoridades estaduais e representantes da comunidade acadêmica, que destacaram a importância do investimento em educação policial de qualidade.</p>
        
        <h3>Novas Instalações</h3>
        <p>As novas instalações incluem:</p>
        <ul>
            <li>Laboratórios modernos de tecnologia</li>
            <li>Salas de aula equipadas com recursos audiovisuais</li>
            <li>Centro de simulação avançado</li>
            <li>Biblioteca com acervo digitalizado</li>
        </ul>
        
        <p>Esta expansão permitirá a ACADEPOL atender um número maior de alunos e oferecer cursos mais especializados, contribuindo para a melhoria contínua dos serviços de segurança pública.</p>';
    }

    /**
     * Gera conteúdo sobre curso
     */
    private function gerarConteudoCurso(): string
    {
        return '<p>Tem início na próxima semana o curso de especialização em investigação criminal, uma das principais capacitações oferecidas pela ACADEPOL para aprimorar as técnicas investigativas dos profissionais.</p>
        
        <p>O curso, com duração de 120 horas, abordará temas como criminalística moderna, análise de evidências digitais e técnicas de interrogatório, sempre respeitando os direitos humanos.</p>
        
        <h3>Metodologia Inovadora</h3>
        <p>A metodologia do curso combina aulas teóricas com práticas em cenários reais, proporcionando aos participantes uma experiência completa e aplicável em suas atividades profissionais.</p>
        
        <p>As inscrições para a próxima turma já estão abertas e podem ser realizadas através do portal da ACADEPOL.</p>';
    }

    /**
     * Gera conteúdo sobre formatura
     */
    private function gerarConteudoFormatura(): string
    {
        return '<p>A ACADEPOL realizou na última sexta-feira a cerimônia de formatura de mais uma turma de agentes, fortalecendo o quadro de profissionais capacitados para atuar na segurança pública estadual.</p>
        
        <p>Foram formados 45 novos agentes que passaram por rigoroso processo de capacitação durante os últimos seis meses, incluindo disciplinas teóricas e práticas.</p>
        
        <h3>Preparação Completa</h3>
        <p>Durante o período de formação, os alunos estudaram:</p>
        <ul>
            <li>Legislação penal e processual penal</li>
            <li>Direitos humanos e cidadania</li>
            <li>Técnicas de investigação</li>
            <li>Armamento e tiro</li>
            <li>Defesa pessoal</li>
        </ul>
        
        <p>A cerimônia contou com a presença de familiares e autoridades, que destacaram o comprometimento e dedicação dos novos profissionais.</p>';
    }

    /**
     * Gera conteúdo sobre equipamentos
     */
    private function gerarConteudoEquipamentos(): string
    {
        return '<p>A ACADEPOL recebeu um importante investimento em equipamentos modernos que irão revolucionar a forma como os treinamentos são conduzidos na instituição.</p>
        
        <p>Entre os novos equipamentos estão simuladores de última geração, sistemas de realidade virtual para treinamento em situações de risco e equipamentos de laboratório para análise criminal.</p>
        
        <h3>Tecnologia a Serviço da Educação</h3>
        <p>Os novos recursos tecnológicos permitirão que os alunos tenham acesso a experiências de aprendizado mais imersivas e próximas da realidade que enfrentarão em campo.</p>
        
        <p>Esta modernização faz parte do plano estratégico da ACADEPOL de manter-se na vanguarda da educação policial, preparando profissionais cada vez mais qualificados.</p>';
    }

    /**
     * Gera conteúdo sobre parceria
     */
    private function gerarConteudoParceria(): string
    {
        return '<p>A ACADEPOL firmou importantes parcerias com universidades renomadas para fortalecer ainda mais a qualidade do ensino oferecido aos futuros profissionais de segurança pública.</p>
        
        <p>Essas parcerias permitirão o intercâmbio de conhecimentos, desenvolvimento de pesquisas conjuntas e acesso a recursos acadêmicos avançados.</p>
        
        <h3>Benefícios da Parceria</h3>
        <p>As principais vantagens incluem:</p>
        <ul>
            <li>Acesso a bibliotecas e bases de dados acadêmicas</li>
            <li>Participação em projetos de pesquisa</li>
            <li>Intercâmbio de professores e pesquisadores</li>
            <li>Certificações reconhecidas academicamente</li>
        </ul>
        
        <p>Esta iniciativa reforça o compromisso da ACADEPOL com a excelência educacional e a formação integral dos profissionais.</p>';
    }

    /**
     * Gera conteúdo sobre programa
     */
    private function gerarConteudoPrograma(): string
    {
        return '<p>A ACADEPOL lança um inovador programa de capacitação em direitos humanos, visando fortalecer a formação ética e cidadã dos profissionais de segurança pública.</p>
        
        <p>O programa aborda temas fundamentais como dignidade humana, uso da força, atendimento a grupos vulneráveis e mediação de conflitos.</p>
        
        <h3>Metodologia Participativa</h3>
        <p>As atividades do programa incluem debates, estudos de caso, simulações e palestras com especialistas renomados na área de direitos humanos.</p>
        
        <p>Esta capacitação é obrigatória para todos os profissionais e será oferecida regularmente como parte da educação continuada.</p>';
    }

    /**
     * Gera conteúdo sobre workshop
     */
    private function gerarConteudoWorkshop(): string
    {
        return '<p>A ACADEPOL promoveu um workshop especializado sobre a integração entre tecnologia e segurança pública, reunindo especialistas e profissionais da área.</p>
        
        <p>O evento abordou temas como inteligência artificial aplicada à investigação, uso de drones em operações policiais e sistemas de reconhecimento facial.</p>
        
        <h3>Discussões Relevantes</h3>
        <p>Entre os principais tópicos discutidos estavam:</p>
        <ul>
            <li>Ética no uso de tecnologias emergentes</li>
            <li>Proteção de dados pessoais</li>
            <li>Eficiência operacional através da tecnologia</li>
            <li>Desafios da implementação tecnológica</li>
        </ul>
        
        <p>O workshop contou com a participação de representantes de empresas de tecnologia e pesquisadores universitários.</p>';
    }

    /**
     * Gera conteúdo sobre intercâmbio
     */
    private function gerarConteudoIntercambio(): string
    {
        return '<p>Instrutores da ACADEPOL participaram de um programa de intercâmbio internacional, visitando academias de polícia em diferentes países para conhecer novas metodologias de ensino.</p>
        
        <p>A experiência proporcionou contato com práticas inovadoras em educação policial e estabeleceu conexões importantes para futuras colaborações.</p>
        
        <h3>Conhecimento Global</h3>
        <p>Os instrutores puderam observar diferentes abordagens em:</p>
        <ul>
            <li>Treinamento em situações de crise</li>
            <li>Uso de tecnologia em sala de aula</li>
            <li>Metodologias de avaliação</li>
            <li>Programas de educação continuada</li>
        </ul>
        
        <p>O conhecimento adquirido será aplicado no aprimoramento dos cursos oferecidos pela ACADEPOL.</p>';
    }

    /**
     * Gera conteúdo sobre atualização
     */
    private function gerarConteudoAtualizacao(): string
    {
        return '<p>O centro de simulação da ACADEPOL recebeu importantes atualizações tecnológicas que irão proporcionar experiências de treinamento ainda mais realistas e eficazes.</p>
        
        <p>As melhorias incluem novos sistemas de áudio e vídeo, cenários interativos e equipamentos de última geração para simulação de situações operacionais.</p>
        
        <h3>Treinamento Imersivo</h3>
        <p>Com as atualizações, os alunos poderão vivenciar situações como:</p>
        <ul>
            <li>Abordagens policiais em diferentes ambientes</li>
            <li>Negociação em situações de crise</li>
            <li>Atendimento a ocorrências de violência doméstica</li>
            <li>Operações de busca e apreensão</li>
        </ul>
        
        <p>Esta modernização reafirma o compromisso da ACADEPOL com a excelência na preparação dos profissionais.</p>';
    }

    /**
     * Gera conteúdo sobre comemoração
     */
    private function gerarConteudoComemoracao(): string
    {
        return '<p>A ACADEPOL celebra hoje seus 25 anos de dedicação à excelência em educação policial, marcando um quarto de século de contribuição para a formação de profissionais qualificados.</p>
        
        <p>Durante estes anos, a instituição formou milhares de profissionais e se consolidou como referência nacional em educação para segurança pública.</p>
        
        <h3>Legado de Excelência</h3>
        <p>Principais conquistas ao longo dos 25 anos:</p>
        <ul>
            <li>Mais de 10.000 profissionais formados</li>
            <li>Implementação de metodologias inovadoras</li>
            <li>Parcerias com instituições nacionais e internacionais</li>
            <li>Modernização constante das instalações</li>
            <li>Reconhecimento como centro de excelência</li>
        </ul>
        
        <p>A comemoração incluiu uma cerimônia especial com a presença de ex-alunos, professores e autoridades, celebrando esta importante marca.</p>';
    }
}