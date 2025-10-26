<?php

namespace App\Observers;

use App\Models\Noticia;
use App\Http\Controllers\NoticiaController;

class NoticiaObserver
{
  /**
   * Handle the Noticia "created" event.
   */
  public function created(Noticia $noticia)
  {
    $this->invalidarCaches();
  }

  /**
   * Handle the Noticia "updated" event.
   */
  public function updated(Noticia $noticia)
  {
    $this->invalidarCaches();
  }

  /**
   * Handle the Noticia "deleted" event.
   */
  public function deleted(Noticia $noticia)
  {
    $this->invalidarCaches();
  }

  /**
   * Handle the Noticia "restored" event.
   */
  public function restored(Noticia $noticia)
  {
    $this->invalidarCaches();
  }

  /**
   * Handle the Noticia "force deleted" event.
   */
  public function forceDeleted(Noticia $noticia)
  {
    $this->invalidarCaches();
  }

  /**
   * Invalidar todos os caches relacionados às notícias
   */
  private function invalidarCaches()
  {
    try {
      NoticiaController::invalidarTodosOsCaches();
    } catch (\Exception $e) {
      \Log::error('Erro ao invalidar caches do observer: ' . $e->getMessage());
    }
  }
}
