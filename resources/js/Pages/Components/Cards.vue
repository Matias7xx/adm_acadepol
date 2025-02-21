<template>
  <div class="container mx-auto px-4 md:px-6 lg:px-8">
    <div class="grid grid-cols-2 sm:grid-cols-2 lg:grid-cols-3 gap-4 md:gap-6 xl:gap-8">
      <div 
        v-for="(item, index) in highlights" 
        :key="index" 
        class="bg-white shadow-lg rounded-2xl overflow-hidden transform transition-all duration-300 hover:scale-105 hover:shadow-xl flex flex-col"
      >
        <!-- Imagem com aspect ratio consistente -->
        <div class="relative w-full pt-[60%]">
          <img 
            :src="item.image" 
            :alt="item.title" 
            class="absolute top-0 left-0 w-full h-full object-cover transition-transform duration-300 hover:scale-105"
            loading="lazy"
          >
        </div>

        <!-- Conteúdo do card -->
        <div class="p-4 md:p-6 flex flex-col flex-grow">
          <h2 class="text-lg md:text-xl lg:text-2xl font-bold text-gray-800 line-clamp-2">
            {{ item.title }}
          </h2>
          
          <p class="text-gray-600 mt-2 md:mt-3 text-sm md:text-base line-clamp-3 flex-grow">
            {{ item.description }}
          </p>
          
          <a 
            :href="item.link" 
            class="inline-flex items-center mt-4 text-yellow-600 hover:text-yellow-700 transition-colors duration-200 font-medium"
          >
            Saiba mais
            <span class="ml-1 text-lg">»</span>
          </a>
        </div>
      </div>
    </div>
  </div>
</template>


  
  <script>
  export default {
    data() {
      return {
        highlights: [
          {
            title: 'Cursos',
            description: 'Confira os cursos disponíveis na ACADEPOL e inscreva-se.',
            image: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTKlu4C2usfgZY4H1yt-mu5_2Q3BYGjh6ZkoQ&s',
            link: '#'
          },
          {
            title: 'Biblioteca Virtual',
            description: 'Acesse materiais e conteúdos exclusivos da ACADEPOL.',
            image: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS-eM5576kLJEjvM2aelyCcgMH7q0ehjzFabw&s',
            link: '#'
          },
          {
            title: 'Processos Seletivos',
            description: 'Fique por dentro dos processos seletivos em andamento.',
            image: 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTUj2ZQ2v5N48GDpww9towbDE6stzkb-VvsgA&s',
            link: '#'
          }
        ]
      };
    }
  };
  </script>
  