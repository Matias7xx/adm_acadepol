export function useNotifications() {
  const showNotification = notification => {
    if (window.NotificationSystem) {
      window.NotificationSystem.show(notification);
    } else {
      // Fallback para console se o sistema não estiver disponível
      console.log(
        `[${notification.type?.toUpperCase() || 'INFO'}] ${notification.title || ''}: ${notification.message}`
      );
    }
  };

  const showSuccess = (message, title = 'Sucesso!') => {
    showNotification({ type: 'success', title, message });
  };

  const showError = (message, title = 'Erro!') => {
    showNotification({ type: 'error', title, message });
  };

  const showWarning = (message, title = 'Atenção!') => {
    showNotification({ type: 'warning', title, message });
  };

  const showInfo = (message, title = null) => {
    showNotification({ type: 'info', title, message });
  };

  return {
    showNotification,
    showSuccess,
    showError,
    showWarning,
    showInfo,
  };
}
