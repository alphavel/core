# Autowiring e Dependency Injection - Guia de Uso

## 🎯 O que é Autowiring?

Autowiring é a capacidade do framework de **resolver automaticamente** as dependências de uma classe olhando para o type hint do construtor, **sem necessidade de configuração manual**.

## ✨ Exemplo de Uso

### Antes (Sem Autowiring)
```php
// ❌ Tinha que instanciar manualmente ou registrar no container
class UserController
{
    private UserService $service;
    
    public function __construct()
    {
        $this->service = new UserService(); // Acoplamento ruim!
    }
}
```

### Depois (Com Autowiring)
```php
// ✅ O container resolve automaticamente!
class UserController
{
    public function __construct(
        private UserService $service,
        private LoggerInterface $logger
    ) {}
    
    public function index(Request $request): Response
    {
        $users = $this->service->all();
        $this->logger->info('Users listed', ['count' => count($users)]);
        
        return Response::json(['users' => $users]);
    }
}
```

**Magic!** O framework automaticamente:
1. Detecta que `UserController` precisa de `UserService` e `LoggerInterface`
2. Cria instâncias dessas dependências
3. Injeta no construtor do Controller
4. Faz isso **uma vez por worker** e cacheia a reflexão

## 🚀 Performance

### Cache de Reflexão

O Alphavel usa um cache inteligente:

```php
// Primeira requisição (1x por worker, ~0.5ms)
UserController → ReflectionClass → detecta dependências → CACHEIA

// Requisições seguintes (~0.001ms)
UserController → lê do cache → instancia → RÁPIDO!
```

**Resultado:** Autowiring com performance idêntica a `new Class()` manual.

## 📚 Exemplos Avançados

### 1. Injeção em Cadeia (Nested Dependencies)

```php
class OrderController
{
    // OrderService também tem dependências!
    public function __construct(private OrderService $service) {}
}

class OrderService
{
    // PaymentGateway também tem dependências!
    public function __construct(
        private PaymentGateway $gateway,
        private LoggerInterface $logger
    ) {}
}

class PaymentGateway
{
    public function __construct(private HttpClient $client) {}
}
```

O container resolve toda a árvore automaticamente! 🌳

### 2. Parâmetros com Valor Padrão

```php
class EmailController
{
    public function __construct(
        private MailService $mailer,
        private string $from = 'noreply@example.com' // ✅ Valor padrão funciona!
    ) {}
}
```

### 3. Interfaces e Bindings

Se você quiser usar interfaces (recomendado):

```php
// No bootstrap/app.php
$app->bind(LoggerInterface::class, function() {
    return new FileLogger(__DIR__ . '/../storage/logs');
});

// No Controller
class UserController
{
    // ✅ Recebe a implementação configurada no bind
    public function __construct(private LoggerInterface $logger) {}
}
```

## ⚠️ Limitações

### ❌ Não funciona com tipos primitivos sem default

```php
// ❌ ERRO! Container não sabe que string passar
public function __construct(private string $apiKey) {}

// ✅ OK! Tem valor padrão
public function __construct(private string $apiKey = 'default') {}

// ✅ OK! Registre no container
$app->singleton('api.key', fn() => env('API_KEY'));
public function __construct(private string $apiKey) {
    // E receba via $app->make('api.key')
}
```

### ✅ Sempre funciona com classes

```php
// ✅ SEMPRE funciona! Classes são auto-resolvidas
public function __construct(
    private UserService $service,
    private CacheInterface $cache,
    private EventDispatcher $events
) {}
```

## 🎓 Boas Práticas

### 1. Use interfaces para flexibilidade
```php
// ✅ Bom: Pode trocar implementação
public function __construct(private CacheInterface $cache) {}

// ❌ Menos flexível: Acoplado à implementação
public function __construct(private RedisCache $cache) {}
```

### 2. Mantenha construtores simples
```php
// ✅ Bom: Apenas dependências
public function __construct(
    private UserRepository $users,
    private MailService $mailer
) {}

// ❌ Ruim: Lógica no construtor
public function __construct(private UserRepository $users) {
    $this->users->connect(); // NÃO faça isso!
}
```

### 3. Controllers devem ser stateless
```php
// ✅ Bom: Sem estado mutável
class UserController
{
    public function __construct(private UserService $service) {}
    
    public function show(Request $request, $id) {
        return $this->service->find($id); // Usa apenas parâmetros
    }
}

// ❌ Ruim: Estado mutável (mas o Transient pattern previne vazamento)
class UserController
{
    private $currentUser; // ⚠️ Evite propriedades mutáveis
    
    public function show(Request $request, $id) {
        $this->currentUser = $this->service->find($id);
    }
}
```

## 📊 Comparação de Performance

| Método | Primeira Req | Próximas Reqs | Obs |
|--------|--------------|---------------|-----|
| `new Class()` manual | 0.001ms | 0.001ms | Sem DI |
| Autowiring (sem cache) | 0.5ms | 0.5ms | Lento! |
| **Autowiring (com cache)** | **0.5ms** | **0.001ms** | ⚡ Rápido! |

**Conclusão:** Após o warmup, autowiring é tão rápido quanto instanciação manual!

## 🔧 Diagnóstico

### Ver o cache de reflexão (debug)
```php
// Em modo dev, você pode inspecionar:
dd(Container::getInstance()->getReflectionCache()); // Não público por padrão
```

### Forçar limpeza do cache
```php
// Ao fazer deploy, reinicie os workers Swoole:
docker-compose restart
# Ou: docker exec <container> kill -USR1 1
```

O cache é armazenado **na memória do worker**, então:
- ✅ Extremamente rápido (RAM)
- ✅ Isolado por worker (sem race conditions)
- ⚠️ Perdido ao reiniciar o worker (não é problema, se reconstrói automaticamente)

## 🎉 Benefícios Finais

1. **Zero Config**: Escreva `class Service {}` e use. Sem registros.
2. **Performance**: Cache de reflexão = velocidade de código manual.
3. **Testabilidade**: Mocks/fakes injetados facilmente nos testes.
4. **Manutenibilidade**: Dependências explícitas no construtor.
5. **Type Safety**: PHP 8+ verifica tipos em tempo de execução.

---

**Alphavel v1.2.0** - Autowiring nativo com cache de reflexão. 🚀
