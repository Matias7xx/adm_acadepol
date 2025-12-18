<script setup>
import SiteNavbar from './Components/SiteNavbar.vue';
import Footer from './Components/Footer.vue';
import { Head } from '@inertiajs/vue3';
import Header from './Components/Header.vue';
</script>

<template>
  <Head title="História" />
  <div class="min-h-screen flex flex-col bg-gray-100">
    <Header />
    <SiteNavbar />

    <section class="w-full bg-gray-50 py-10 sm:py-14 md:py-10">
      <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow-md rounded-lg p-6 sm:p-8 md:p-12 lg:p-16">
          <!-- Cabeçalho Principal -->
          <header class="mb-10 sm:mb-12 md:mb-10">
            <h1
              class="text-xl sm:text-2xl md:text-3xl lg:text-4xl font-bold text-gray-900 mb-4 sm:mb-6 leading-tight"
            >
              História e Trajetória da Academia de Ensino da Polícia Civil
              (ACADEPOL/PB)
            </h1>
            <p
              class="text-base sm:text-lg md:text-xl text-gray-600 leading-relaxed"
            >
              A Academia de Ensino da Polícia Civil da Paraíba - ACADEPOL, é o
              pilar da formação e do aperfeiçoamento contínuo dos nossos
              policiais civis, dedicando-se à excelência e à modernização da
              segurança pública no estado.
            </p>
          </header>

          <div class="border-t border-gray-200 mb-10 sm:mb-10"></div>

          <!-- Seção: O Início de uma Missão -->
          <section class="mb-10 sm:mb-12 md:mb-10">
            <h2
              class="text-xl sm:text-2xl md:text-3xl font-bold text-[#bea55a] mb-4 sm:mb-5 md:mb-6"
            >
              O Início de uma Missão
            </h2>
            <div
              class="prose prose-base sm:prose-lg max-w-none text-gray-700 space-y-4 sm:space-y-5"
            >
              <p class="text-justify">
                Nossa história começa oficialmente com a
                <strong>Lei nº 4.216, de 17 de dezembro de 1980</strong>, que
                deu existência legal à instituição, subordinada à Secretaria de
                Segurança Pública. O primeiro passo foi dado em uma sede
                provisória no bairro do Miramar, conhecida como Núcleo de
                Formação Policial.
              </p>
              <p class="text-justify">
                Em 1980, a ACADEPOL realizou seu primeiro curso de formação,
                capacitando Delegados, Agentes e Escrivães de Polícia. Desde
                então, <strong>15 Diretores</strong> estiveram à frente da
                gestão, cada um contribuindo para a construção e o aprimoramento
                contínuo da nossa missão. O primeiro diretor,
                <strong>Dr. Eugênio Murilo S. Lemos Junior</strong>, é hoje
                homenageado ao emprestar seu nome à nossa Biblioteca, um
                reconhecimento àqueles que moldaram o caminho da instituição.
              </p>
              <p class="text-justify">
                O reconhecimento formal veio em
                <strong>14 de abril de 1982</strong>, com a aprovação do
                Regimento Interno, que estruturou a Academia para assumir uma
                posição de destaque no cenário da Segurança Pública.
              </p>
            </div>
          </section>

          <div class="border-t border-gray-200 mb-10 sm:mb-10"></div>

          <!-- Seção: Evolução e Modernização -->
          <section class="mb-10 sm:mb-12 md:mb-10">
            <h2
              class="text-xl sm:text-2xl md:text-3xl font-bold text-[#bea55a] mb-4 sm:mb-5 md:mb-6"
            >
              Evolução e Modernização
            </h2>
            <div
              class="prose prose-base sm:prose-lg max-w-none text-gray-700 space-y-4 sm:space-y-5"
            >
              <p class="text-justify">
                Ao longo de mais de quatro décadas, a ACADEPOL tem evoluído
                constantemente. Em 2007, a instituição teve seu nome modificado
                para <strong>Academia de Ensino de Polícia</strong> (Lei
                Orgânica n.º 8.186), refletindo sua missão ampliada de não
                apenas formar, mas garantir a educação continuada e o
                aperfeiçoamento de todo o corpo policial.
              </p>
              <p class="text-justify">
                A busca por um ensino de vanguarda levou a grandes
                transformações:
              </p>

              <div
                class="mt-6 sm:mt-8 space-y-6 sm:space-y-7 pl-4 sm:pl-6 border-l-2 border-gray-200"
              >
                <div>
                  <h3
                    class="text-lg sm:text-xl font-semibold text-gray-900 mb-2 sm:mb-3"
                  >
                    Reestruturação Curricular
                  </h3>
                  <p class="text-justify">
                    A grade de Cursos de Formação foi totalmente reformulada,
                    alinhando-se à Matriz Curricular Nacional. Os cursos
                    passaram a ter carga-horária ampliada e um foco mais social,
                    humanista e técnico-operacional, preparando o policial para
                    a prevenção e a repressão qualificadas.
                  </p>
                </div>

                <div>
                  <h3
                    class="text-lg sm:text-xl font-semibold text-gray-900 mb-2 sm:mb-3"
                  >
                    Interiorização
                  </h3>
                  <p class="text-justify">
                    Visando a inclusão, a ACADEPOL passou a promover cursos no
                    interior do estado, garantindo que policiais de todas as
                    regiões tivessem acesso ao processo de qualificação
                    profissional.
                  </p>
                </div>

                <div>
                  <h3
                    class="text-lg sm:text-xl font-semibold text-gray-900 mb-2 sm:mb-3"
                  >
                    Compromisso Social
                  </h3>
                  <p class="text-justify">
                    Em um projeto inovador, a Academia atuou na prevenção da
                    violência, promovendo ações de Ética, Cidadania e Direitos
                    Humanos para crianças de comunidades carentes, demonstrando
                    seu papel para além das investigações.
                  </p>
                </div>
              </div>
            </div>
          </section>

          <div class="border-t border-gray-200 mb-10 sm:mb-10"></div>

          <!-- Seção: A Nova Sede -->
          <section class="mb-10 sm:mb-12 md:mb-10">
            <h2
              class="text-xl sm:text-2xl md:text-3xl font-bold text-[#bea55a] mb-4 sm:mb-5 md:mb-6"
            >
              A Nova Sede: Estrutura de Ponta
            </h2>
            <div
              class="prose prose-base sm:prose-lg max-w-none text-gray-700 space-y-4 sm:space-y-5"
            >
              <p class="text-justify">
                Após anos de atuação na sede do bairro de Mangabeira, a ACADEPOL
                inaugurou em <strong>2013</strong> sua nova e moderna sede,
                localizada no bairro <strong>Costa do Sol</strong>.
              </p>
              <p class="text-justify">
                A nova estrutura é uma das mais completas do país e foi
                projetada para oferecer o melhor em treinamento policial,
                contando com:
              </p>

              <ul
                class="space-y-2 sm:space-y-3 mt-4 sm:mt-6 list-disc list-inside"
              >
                <li>15 Salas de Aula e um Auditório para 300 pessoas</li>
                <li>
                  Ambiente para Treinamento Avançado de Tiro, com cenários
                  temáticos para simulações realistas
                </li>
                <li>
                  Complexo Esportivo completo, incluindo campo de futebol,
                  ginásio poliesportivo, quadra de areia e academia de
                  musculação
                </li>
                <li>Biblioteca e Alojamento</li>
              </ul>
            </div>
          </section>

          <div class="border-t border-gray-200 mb-10 sm:mb-10"></div>

          <!-- Seção Final -->
          <section>
            <div
              class="prose prose-base sm:prose-lg max-w-none text-gray-700 space-y-4 sm:space-y-5"
            >
              <p class="text-justify">
                Com essa trajetória de dedicação, investimento e renovação
                contínua, a Academia de Ensino da Polícia Civil da Paraíba
                reafirma seu compromisso em formar profissionais altamente
                qualificados, éticos e preparados para servir e proteger a
                população paraibana.
              </p>
              <p class="text-justify">
                Ao longo de sua história, a ACADEPOL teve a honra de formar mais
                de <strong>2 mil policiais civis</strong>, contribuindo
                decisivamente para a segurança e a justiça em todo o estado.
              </p>
            </div>
          </section>
        </div>
      </div>
    </section>

    <Footer />
  </div>
</template>
