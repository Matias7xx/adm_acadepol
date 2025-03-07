<script setup>
import { Head, Link, useForm } from "@inertiajs/vue3"
import {
  mdiInformationOutline,
  mdiArrowLeftBoldOutline,
  mdiArrowRightBoldOutline
} from "@mdi/js"
import LayoutAuthenticated from "@/Layouts/Admin/LayoutAuthenticated.vue"
import SectionMain from "@/Components/SectionMain.vue"
import SectionTitleLineWithButton from "@/Components/SectionTitleLineWithButton.vue"
import CardBox from "@/Components/CardBox.vue"
import BaseButton from "@/Components/BaseButton.vue"

const props = defineProps({
  curso: {
    type: Object,
    default: () => ({}),
  }
})
</script>

<template>
  <LayoutAuthenticated>
    <Head title="Detalhes Curso" />
    <SectionMain>
      <SectionTitleLineWithButton
        :icon="mdiInformationOutline"
        title="Detalhes do Curso"
        main
      >
        <BaseButton
          :route-name="route('admin.cursos.index')"
          :icon="mdiArrowLeftBoldOutline"
          label="Voltar"
          color="white"
          rounded-full
          small
        />
      </SectionTitleLineWithButton>
      <CardBox class="mb-6">
        <table>
          <tbody>
            <tr>
              <td
                class="
                  p-4
                  pl-8
                  text-slate-500
                  dark:text-slate-400
                  hidden
                  lg:block
                "
              >
                Nome
              </td>
              <td data-label="Curso">
                {{ curso.nome }}
              </td>
            </tr>
            <tr>
              <td
                class="
                  p-4
                  pl-8
                  text-slate-500
                  dark:text-slate-400
                  hidden
                  lg:block
                "
              >
                Descrição
              </td>
              <td data-label="Descrição">
                {{ curso.descricao }}
              </td>
            </tr>
            <tr>
              <td
                class="
                  p-4
                  pl-8
                  text-slate-500
                  dark:text-slate-400
                  hidden
                  lg:block
                "
              >
                Início do Curso
              </td>
              <td data-label="Data Inicio">
                {{new Date(curso.data_inicio).toLocaleString()}}
              </td>
            </tr>

            <tr>
              <td
                class="
                  p-4
                  pl-8
                  text-slate-500
                  dark:text-slate-400
                  hidden
                  lg:block
                "
              >
                Fim do Curso
              </td>
              <td data-label="Data Fim">
                {{ new Date(curso.data_fim).toLocaleString() }}
              </td>
            </tr>

            <tr>
              <td
                class="
                  p-4
                  pl-8
                  text-slate-500
                  dark:text-slate-400
                  hidden
                  lg:block
                "
              >
                Carga Horária
              </td>
              <td data-label="Carga Horaria">
                {{ curso.carga_horaria }} HORAS
              </td>
            </tr>

            <tr>
              <td
                class="
                  p-4
                  pl-8
                  text-slate-500
                  dark:text-slate-400
                  hidden
                  lg:block
                "
              >
                Capacidade de Alunos
              </td>
              <td data-label="Capacidade">
                {{ curso.capacidade_maxima }}
              </td>
            </tr>

            <tr>
              <td
                class="
                  p-4
                  pl-8
                  text-slate-500
                  dark:text-slate-400
                  hidden
                  lg:block
                "
              >
                Modalidade
              </td>
              <td data-label="Modalidade">
                {{ curso.modalidade }}
              </td>
            </tr>

            <tr>
              <td
                class="
                  p-4
                  pl-8
                  text-slate-500
                  dark:text-slate-400
                  hidden
                  lg:block
                "
              >
                Localização
              </td>
              <td data-label="Localizacao">
                {{ curso.localizacao }}
              </td>
            </tr>
          </tbody>
        </table>
      </CardBox>
      <BaseButton
          :route-name="route('admin.matriculas.index')"
          :icon="mdiArrowRightBoldOutline"
          :id="curso.id"
          label="Matrículas"
          color="white"
          rounded-full
          small
        />
    </SectionMain>
  </LayoutAuthenticated>
</template>