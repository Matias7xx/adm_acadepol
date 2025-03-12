<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

//melhorar o desempenho das consultas ao banco de dados
trait CacheableQueries
{
    /**
     * Tempo de cache padrão em minutos
     */
    protected static $defaultCacheTime = 60;
    
    /**
     * Armazena a query em cache e retorna o resultado
     *
     * @param Builder $query
     * @param string $key
     * @param int|null $time
     * @return mixed
     */
    public function scopeCacheQuery(Builder $query, string $key, ?int $time = null)
    {
        $time = $time ?? static::$defaultCacheTime;
        
        return Cache::remember($key, $time, function() use ($query) {
            return $query->get();
        });
    }
    
    /**
     * Armazena o resultado paginado em cache
     *
     * @param Builder $query
     * @param int $perPage
     * @param string $key
     * @param int|null $time
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function scopeCachePaginate(Builder $query, int $perPage, string $key, ?int $time = null)
    {
        $time = $time ?? static::$defaultCacheTime;
        
        return Cache::remember($key, $time, function() use ($query, $perPage) {
            return $query->paginate($perPage);
        });
    }
    
    /**
     * Busca por um modelo em cache ou banco de dados
     *
     * @param Builder $query
     * @param mixed $id
     * @param array $columns
     * @param int|null $time
     * @return mixed
     */
    public function scopeCacheFind(Builder $query, $id, array $columns = ['*'], ?int $time = null)
    {
        $time = $time ?? static::$defaultCacheTime;
        $key = static::class . ':' . $id;
        
        return Cache::remember($key, $time, function() use ($query, $id, $columns) {
            return $query->find($id, $columns);
        });
    }
    
    /**
     * Limpa o cache de um modelo específico
     *
     * @param mixed $id
     * @return void
     */
    public static function clearCacheForModel($id): void
    {
        $key = static::class . ':' . $id;
        Cache::forget($key);
    }
    
    /**
     * Limpa todo o cache relacionado ao modelo
     *
     * @return void
     */
    public static function clearCache(): void
    {
        $modelName = strtolower(class_basename(static::class));
        $pattern = $modelName . '_*';
        
        if (config('cache.default') === 'redis') {
            // Para Redis, podemos usar o comando SCAN para encontrar chaves
            $redis = Cache::getRedis();
            $prefix = config('cache.prefix') . ':';
            $iterator = null;
            $keys = [];
            
            // Buscar chaves que correspondem ao padrão 
            do {
                $result = $redis->scan($iterator, 'MATCH', $prefix . $pattern, 'COUNT', 100);
                $iterator = $result[0];
                $keys = array_merge($keys, $result[1]);
            } while ($iterator !== 0);
            
            // Remover as chaves encontradas
            if (!empty($keys)) {
                $redis->del($keys);
            }
        } elseif (config('cache.default') === 'file') {
            // Para o driver file, vamos limpar todo o cache
            // Isso é menos eficiente, mas garante que os caches sejam limpos
            Cache::flush();
        }
    }
    
    /**
     * Método hook para limpar cache após salvar
     *
     * @return void
     */
    public static function bootCacheableQueries(): void
    {
        static::saved(function ($model) {
            static::clearCacheForModel($model->id);
            static::clearCache();
        });
        
        static::deleted(function ($model) {
            static::clearCacheForModel($model->id);
            static::clearCache();
        });
    }
}