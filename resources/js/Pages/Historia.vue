<script setup>
  import SiteNavbar from './Components/SiteNavbar.vue';
  import Footer from './Components/Footer.vue';
  import { ref } from 'vue';
  import { Head } from '@inertiajs/vue3';
  import Acadepol1 from '@/src/assets/acadepol1.jpg'
  import Placa from '@/src/assets/PLACA-ACADEPOL.jpg'
  import Evolucao from '@/src/assets/evolucao.jpeg'
  import Header from './Components/Header.vue';

  const historia = 
  { 
    title: "Fundação e Primeiros Anos (1980-1982)", 
    description: "A Academia de Ensino da Polícia Civil passou a ter existência legal com a Lei nº 4.216 de 17/12/1980, publicada no Diário oficial de 18/12/80, no governo de Tarcísio de Miranda Burity, e estava subordinada à Secretaria de Segurança Pública, cujo secretário era o Cel. Geraldo Amorim Navarro que muito se identificou com os ideais da Polícia Civil e abraçou com simpatia a defesa da classe. Inicialmente, a Academia funcionou em sede provisória denominada “Núcleo de Formação Policial”, localizada no bairro do Miramar. Através do Decreto nº 9.426, de 14 de abril de 1982, publicado no Diário Oficial do Estado de 17/04/ 1982, foi aprovado o Regimento Interno da Academia de Polícia Civil do Estado da Paraíba – ACADEPOL, cuja estrutura e funcionamento passaram a obedecer àquele Regimento e assim teve a sua finalidade e competência definidas, funcionando com a estrutura de Diretoria, Divisão de Pesquisa e Programação, Divisão de Ensino, Divisão de Acompanhamento e Avaliação, Divisão de Apoio ao Corpo Discente, Museu Policial, Divisão Administrativa, Congregação e Conselho Técnico. A partir de então a Academia assumiu uma posição de destaque entre os órgãos que integram a Secretaria de Segurança Pública.", 
    image: Placa 
  }

  const estrutura = 
  { 
    title: "Estrutura e Localização", 
    description: "A Academia de Polícia passou a funcionar em sede própria, com excelentes instalações para a época, na Av. Hilton Souto Maior, S/Nº, Mangabeira, sendo considerada uma das melhores do País, onde funcionou até agosto de 2013. O primeiro curso de formação ocorreu em 1980, onde foram formados Delegados de Polícia (480 horas/aula), Agentes de Investigação (460 horas/aula) e Escrivães de Polícia (420 horas/aula). O curso durava em média 03 meses e eram abordadas disciplinas de Direito, Técnicas de Investigação e Tiro Defensivo. O primeiro Diretor da Academia de Polícia foi o Dr. Eugênio Murilo S. Lemos Junior que hoje empresta o nome à nossa Biblioteca. Através da Portaria Ministerial n.º 512/MJ, de 05 de dezembro de 1984 (DOU de 06/12/1984) a Academia se tornou competente para promover Cursos de Formação de Vigilantes, o que posteriormente foi revogado pelo Decreto n.º 99.244 de 10 de maio de 1990, publicado no DOU em 10/05/90.", 
    image: Acadepol1 
  }

  const evolucao = 
  { 
    title: "Evolução Institucional", 
    description: "A Academia de Polícia teve o seu nome modificado para Academia de Ensino de Polícia com a Lei Orgânica n.º 8.186 de 17 de março de 2007, sendo conhecida popularmente como Acadepol. Em alguns momentos de sua existência a Academia de Ensino de Polícia oscilou entre pontos altos e baixos, chegando a ser cogitado, em tempos de decadência, a sua extinção. Todavia, em decorrência de esforços incansável para a conscientização da importância desta Instituição de ensino na formação, capacitação e valorização do profissional de Segurança Pública, a Acadepol tem vivenciado e mantido o seu pleno funcionamento. Consciente de sua missão de treinar e repassar valores ao seu corpo policial, a Academia de Ensino não se limitou apenas em formar o Policial Civil, mas trabalha sistematicamente o aperfeiçoamento, através da Educação Continuada.", 
    image: Evolucao
  }

  </script>

<template>
  <Head title="História" />
  <div class="min-h-screen flex flex-col bg-gray-100">
    <Header />
    <SiteNavbar />

    <section class="w-full bg-gray-100 py-10">
      <div class="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8 space-y-10">
        
        <!-- Bloco 1 -->
        <div class="flex flex-col md:grid md:grid-cols-2 gap-8 md:gap-12 items-center">
          <!-- Imagem 1 -->
          <div class="w-full flex justify-center md:justify-start">
            <img 
              :src="historia.image" 
              :alt="historia.title"
              class="w-full max-w-sm md:max-w-full h-auto object-cover rounded-lg shadow-lg transform transition-all duration-300 hover:scale-105"
            />
          </div>

          <!-- Texto 1 -->
          <div class="text-left md:text-center">
            <h2 class="text-2xl sm:text-3xl font-bold text-[#bea55a] mb-4">
              {{ historia.title }}
            </h2>
            <p class="text-gray-700 leading-relaxed">
              {{ historia.description }}
            </p>
          </div>
        </div>

        <!-- Bloco 2 (Alternado) -->
        <div class="flex flex-col md:grid md:grid-cols-2 gap-8 md:gap-12 items-center">
          <!-- Texto 2 -->
          <div class="text-center">
            <h2 class="text-2xl sm:text-3xl font-bold text-[#bea55a] mb-4">
              {{ estrutura.title }}
            </h2>
            <p class="text-gray-700 leading-relaxed">
              {{ estrutura.description }}
            </p>
          </div>

          <!-- Imagem 2 -->
          <div class="w-full flex justify-center md:justify-end">
            <img 
              :src="estrutura.image" 
              :alt="historia.title"
              class="w-full max-w-sm md:max-w-full h-auto object-cover rounded-lg shadow-lg transform transition-all duration-300 hover:scale-105"
            />
          </div>
        </div>


        <!-- Bloco 3 -->
        <div class="flex flex-col md:grid md:grid-cols-2 gap-8 md:gap-12 items-center">
          <!-- Imagem 3 -->
          <div class="w-full flex justify-center md:justify-start">
            <img 
              :src="evolucao.image" 
              :alt="evolucao.title"
              class="w-full max-w-sm md:max-w-full h-auto object-cover rounded-lg shadow-lg transform transition-all duration-300 hover:scale-105"
            />
          </div>

          <!-- Texto 3 -->
          <div class="text-center md:text-center">
            <h2 class="text-2xl sm:text-3xl font-bold text-[#bea55a] mb-4">
              {{ evolucao.title }}
            </h2>
            <p class="text-gray-700 leading-relaxed">
              {{ evolucao.description }}
            </p>
          </div>
        </div>

      </div>
    </section>

    <Footer />
  </div>
</template>



  