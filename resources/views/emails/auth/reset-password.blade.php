@component('mail::message')
# Redefinição de Senha

Olá **{{ $user->name }}**,

Você está recebendo este e-mail porque recebemos uma solicitação de redefinição de senha para sua conta no sistema da *ACADEPOL*.

@component('mail::button', ['url' => $url])
Redefinir Senha
@endcomponent

**Informações importantes:**
- Este link de redefinição expirará em **{{ $count }} minutos**
- Se você não solicitou esta redefinição, ignore este e-mail
- Por segurança, não compartilhe este link com ninguém

Se você está tendo problemas para clicar no botão "Redefinir Senha", copie e cole a URL abaixo em seu navegador:

{{ $url }}

---

Atenciosamente,<br>
*Academia de Polícia Civil*

@endcomponent