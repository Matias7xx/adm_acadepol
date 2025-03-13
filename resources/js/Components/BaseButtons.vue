<script>
import { h, defineComponent } from 'vue'

export default defineComponent({
  name: 'BaseButtons',
  props: {
    // Opção para não envolver os botões
    noWrap: {
      type: Boolean,
      default: false
    },
    // Tipo de alinhamento dos botões
    type: {
      type: String,
      default: 'justify-start',
      validator: (value) => [
        'justify-start', 
        'justify-end', 
        'justify-center', 
        'justify-between', 
        'justify-around'
      ].includes(value)
    },
    // Espaçamento entre botões
    spacing: {
      type: String,
      default: 'mr-3 last:mr-0 mb-3'
    },
    // Margem bottom para o container
    mb: {
      type: String,
      default: '-mb-3'
    },
    // Direção dos botões (horizontal ou vertical)
    direction: {
      type: String,
      default: 'row',
      validator: (value) => ['row', 'column'].includes(value)
    }
  },
  render () {
    const hasSlot = this.$slots && this.$slots.default

    // Classes base para o container de botões
    const parentClass = [
      'flex',
      'items-center',
      this.type,
      this.direction === 'row' ? 'flex-row' : 'flex-col',
      this.noWrap ? 'flex-nowrap' : 'flex-wrap'
    ]

    // Adicionar margem bottom se especificada
    if (this.mb) {
      parentClass.push(this.mb)
    }

    return h(
      'div',
      { class: parentClass },
      hasSlot
        ? this.$slots.default().map(element => {
          // Verificação para elementos aninhados
          if (element && element.children && typeof element.children === 'object') {
            return h(
              element,
              {},
              element.children.map(child => {
                return h(child, { 
                  class: [
                    this.spacing, 
                    // Adicionar classe de direção para espaçamento vertical
                    this.direction === 'column' ? 'mb-2 last:mb-0 mr-0' : ''
                  ]
                })
              }))
          }

          // Adicionar classes de espaçamento para elementos de primeiro nível
          return h(element, { 
            class: [
              this.spacing, 
              // Adicionar classe de direção para espaçamento vertical
              this.direction === 'column' ? 'mb-2 last:mb-0 mr-0' : ''
            ] 
          })
        })
        : null
    )
  }
})
</script>