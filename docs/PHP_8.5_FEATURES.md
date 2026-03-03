# PHP 8.5 - Características y Novedades

## Información General

**Fecha de lanzamiento:** 20 de noviembre de 2025  
**Soporte activo:** Hasta el 31 de diciembre de 2027 (2 años)  
**Actualizaciones de seguridad:** Hasta el 31 de diciembre de 2029 (4 años en total)

PHP 8.5 es una actualización importante del lenguaje que introduce características clave como el operador pipe, la extensión URI nativa, y mejoras en la clonación de objetos. Esta versión se enfoca en hacer el código más limpio, rápido y fácil de depurar.

## Características Principales de PHP 8.5

### 1. Operador Pipe (`|>`)

El operador pipe permite encadenar funciones de izquierda a derecha, pasando el resultado de una función como entrada de la siguiente. Es una de las características más esperadas por la comunidad PHP.

**Sintaxis básica:**

```php
$result = "Hello World"
    |> strtoupper(...)
    |> str_shuffle(...)
    |> trim(...);
// Resultado: "LWHO LDLROE"
```

**Antes de PHP 8.5 (funciones anidadas):**

```php
$title = ' PHP 8.5 Released ';
$slug = strtolower(
    str_replace('.', '',
        str_replace(' ', '-',
            trim($title)
        )
    )
);
```

**Con PHP 8.5 (operador pipe):**

```php
$title = ' PHP 8.5 Released ';
$slug = $title
    |> trim(...)
    |> (fn($str) => str_replace(' ', '-', $str))
    |> (fn($str) => str_replace('.', '', $str))
    |> strtolower(...);
```

**Tipos de callables soportados:**

```php
$result = "Hello World"
    |> 'strtoupper'                              // String callable
    |> str_shuffle(...)                          // First-class callable
    |> fn($x) => trim($x)                        // Arrow function
    |> function(string $x): string {             // Anonymous function
        return strtolower($x);
    }
    |> new MyClass()                             // Invokable object
    |> [MyClass::class, 'myStaticMethod']        // Static method
    |> new MyClass()->myInstanceMethod(...)      // Instance method
    |> my_function(...);                         // Named function
```

**Beneficios:**

- Código más legible y fácil de seguir
- Flujo de datos de izquierda a derecha (como se lee naturalmente)
- Elimina variables intermedias innecesarias
- Facilita la composición funcional
- Cada paso es aislado y testeable

### 2. Extensión URI Nativa

PHP 8.5 introduce una extensión URI completa que soporta dos estándares: RFC 3986 y WHATWG URL. Reemplaza las funciones inconsistentes como `parse_url()`.

**RFC 3986 (Estándar Web Tradicional):**

```php
use Uri\Rfc3986\Uri;

$uri = Uri::fromString('https://user:pass@example.com:443/path?query=value#fragment');

echo $uri->getScheme();      // "https"
echo $uri->getAuthority();   // "user:pass@example.com:443"
echo $uri->getHost();        // "example.com"
echo $uri->getPath();        // "/path"
echo $uri->getQuery();       // "query=value"
echo $uri->getFragment();    // "fragment"
```

**WHATWG (Estándar de Navegadores Modernos):**

```php
use Uri\WhatWg\Url;

$url = Url::fromString('https://example.com/path/../other');
echo $url->getPathname();    // "/other" (¡normalizado automáticamente!)
echo $url->getOrigin();      // "https://example.com"
```

**Manejo de codificación:**

```php
$uri = new Uri\Rfc3986\Uri("https://%61pple:p%61ss@ex%61mple.com/foob%61r?%61bc=%61bc");

// Versiones "raw" (sin decodificar)
echo $uri->getRawUserInfo(); // "%61pple:p%61ss"
echo $uri->getRawHost();     // "ex%61mple.com"
echo $uri->getRawPath();     // "/foob%61r"

// Versiones normalizadas (decodificadas)
echo $uri->getUserInfo();    // "apple:pass"
echo $uri->getHost();        // "example.com"
echo $uri->getPath();        // "/foobar"
```

**Modificación de URIs:**

```php
use Uri\Rfc3986\Uri;

$url = new Uri('HTTPS://thephp.foundation:443/sp%6Fnsor/');

$defaultPortForScheme = match ($url->getScheme()) {
    'http' => 80,
    'https' => 443,
    'ssh' => 22,
    default => null,
};

// Remover puertos por defecto
if ($url->getPort() === $defaultPortForScheme) {
    $url = $url->withPort(null);
}

echo $url->toString();       // https://thephp.foundation/sponsor/
echo $url->toRawString();    // HTTPS://thephp.foundation/sp%6Fnsor/
```

**Beneficios:**

- Parsing conforme a estándares (RFC 3986 y WHATWG)
- Seguridad mejorada (usa bibliotecas probadas: uriparser y Lexbor)
- Manejo consistente de URLs
- Soporte para Unicode e IDNA
- Normalización automática

### 3. Clone With (Clonación con Modificaciones)

Permite clonar objetos y modificar propiedades en una sola operación, ideal para clases `readonly`.

**Antes de PHP 8.5:**

```php
readonly class Color {
    public function __construct(
        public int $red,
        public int $green,
        public int $blue,
        public int $alpha = 255,
    ) {}

    public function withAlpha(int $alpha): self {
        $values = get_object_vars($this);
        $values['alpha'] = $alpha;
        return new self(...$values);
    }
}

$blue = new Color(79, 91, 147);
$transparentBlue = $blue->withAlpha(128);
```

**Con PHP 8.5:**

```php
readonly class Color {
    public function __construct(
        public int $red,
        public int $green,
        public int $blue,
        public int $alpha = 255,
    ) {}

    public function withAlpha(int $alpha): self {
        return clone($this, ['alpha' => $alpha]);
    }
}

$blue = new Color(79, 91, 147);
$transparentBlue = $blue->withAlpha(128);
```

**Ejemplo con múltiples propiedades:**

```php
readonly class Response {
    public function __construct(
        public int $statusCode,
        public string $body,
    ) {}
}

$response = new Response(200, 'OK');
$error = clone($response, [
    'statusCode' => 500,
    'body' => 'Internal Server Error',
]);
```

**Beneficios:**

- Patrón "wither" simplificado
- Menos código boilerplate
- Perfecto para objetos inmutables
- Sintaxis más clara y concisa

### 4. Atributo `#[\NoDiscard]`

Emite una advertencia cuando el valor de retorno de una función es ignorado.

```php
#[\NoDiscard]
function calculateTotal(array $items): float {
    return array_sum(array_column($items, 'price'));
}

// Esto genera una advertencia:
calculateTotal($items); // ⚠️ Warning: Result of calculateTotal() is not used

// Uso correcto:
$total = calculateTotal($items); // ✓ OK
```

**Casos de uso:**

- Funciones de sanitización
- Operaciones de transformación
- Cálculos importantes
- Prevenir errores comunes

### 5. Nuevas Funciones de Array

**`array_first()` y `array_last()`:**

```php
$users = ["Alice", "Avery", "Scott", "Steph"];

echo array_first($users); // "Alice"
echo array_last($users);  // "Steph"

// También funciona con arrays asociativos
$person = ["name" => "Scott", "city" => "Saginaw"];
echo array_first($person); // "Scott"
echo array_last($person);  // "Saginaw"
```

### 6. Mejoras en Filtros y Validación

Nueva bandera para lanzar excepciones en validaciones fallidas:

```php
// Antes: retornaba false
$email = filter_var($input, FILTER_VALIDATE_EMAIL);
if ($email === false) {
    throw new InvalidArgumentException('Invalid email');
}

// Ahora: puede lanzar excepción directamente (¡NOTA: el flag correcto es FILTER_THROW_ON_FAILURE!)
try {
    $email = filter_var($input, FILTER_VALIDATE_EMAIL, FILTER_THROW_ON_FAILURE);
} catch (ValueError $e) {
    // Manejar error de validación
}
```

### 7. Stack Traces para Errores Fatales

Los errores fatales ahora incluyen stack traces completos por defecto, facilitando la depuración.

```php
// Antes: solo mensaje de error
Fatal error: Maximum execution time exceeded

// Ahora: con stack trace completo
Fatal error: Maximum execution time exceeded in /path/to/file.php:42
Stack trace:
#0 /path/to/file.php(42): longRunningFunction()
#1 /path/to/file.php(15): processData()
#2 {main}
```

### 8. Nuevas Funciones de Internacionalización

**`locale_is_right_to_left()` y `Locale::isRightToLeft()`:**

```php
// Detectar si un locale usa escritura de derecha a izquierda
$isRTL = locale_is_right_to_left('ar_SA'); // true (árabe)
$isRTL = locale_is_right_to_left('en_US'); // false (inglés)

// Versión orientada a objetos
$isRTL = Locale::isRightToLeft('he_IL'); // true (hebreo)
```

**Nueva clase `IntlListFormatter`:**

```php
$formatter = new IntlListFormatter('en_US', IntlListFormatter::TYPE_AND);
echo $formatter->format(['apples', 'oranges', 'bananas']);
// "apples, oranges, and bananas"

$formatter = new IntlListFormatter('es_ES', IntlListFormatter::TYPE_OR);
echo $formatter->format(['manzanas', 'naranjas', 'plátanos']);
// "manzanas, naranjas o plátanos"
```

### 9. Mejoras en CLI

**`php --ini=diff`:** Muestra solo las directivas INI que difieren de los valores por defecto.

```bash
php --ini=diff

# Salida:
memory_limit = 512M (default: 128M)
max_execution_time = 300 (default: 30)
display_errors = On (default: Off)
```

### 10. Nuevas Funciones de Gestión de Errores

```php
// Obtener el manejador de excepciones actual
$handler = get_exception_handler();

// Obtener el manejador de errores actual
$handler = get_error_handler();
```

### 11. Mejoras en Atributos

- Los atributos ahora pueden aplicarse a constantes
- `#[\Override]` puede aplicarse a propiedades
- `#[\Deprecated]` puede usarse en traits y constantes
- Nuevo atributo `#[\DelayedTargetValidation]` para suprimir errores de compilación

```php
class Example {
    #[\Deprecated("Use NEW_CONSTANT instead")]
    public const OLD_CONSTANT = 'old';

    public const NEW_CONSTANT = 'new';

    #[\Override]
    public string $property = 'value';
}
```

### 12. Propiedades Estáticas con Visibilidad Asimétrica

```php
class Config {
    public private(set) static string $apiKey;

    public static function initialize(string $key): void {
        self::$apiKey = $key; // ✓ OK dentro de la clase
    }
}

Config::initialize('secret-key');
echo Config::$apiKey;     // ✓ OK: lectura pública
Config::$apiKey = 'new';  // ✗ Error: escritura privada
```

### 13. Propiedades `final` en Constructor Property Promotion

```php
class User {
    public function __construct(
        public final string $id,  // No puede ser sobrescrita en clases hijas
        public string $name,
    ) {}
}
```

### 14. Nuevas Funciones y Constantes

**Constantes:**

- `PHP_BUILD_PROVIDER`: Proveedor que compiló PHP
- `PHP_BUILD_DATE`: Fecha de compilación de PHP

**Funciones cURL:**

- `curl_multi_get_handles()`: Obtiene todos los handles de una sesión multi-cURL

**Funciones DOM:**

- `Dom\Element::getElementsByClassName()`: Buscar elementos por clase
- `Dom\Element::insertAdjacentHTML()`: Insertar HTML en posiciones relativas

**Funciones de grafemas:**

- `grapheme_levenshtein()`: Calcular distancia Levenshtein con soporte Unicode

### 15. Mejoras en Closures

**`Closure::getCurrent()`:** Simplifica la recursión en funciones anónimas.

```php
// Antes: necesitabas una variable
$factorial = function(int $n) use (&$factorial): int {
    return $n <= 1 ? 1 : $n * $factorial($n - 1);
};

// Ahora: más simple
$factorial = function(int $n): int {
    return $n <= 1 ? 1 : $n * Closure::getCurrent()($n - 1);
};
```

### 16. Soporte para Cookies Particionadas (CHIPS)

```php
// setcookie() y setrawcookie() ahora soportan "partitioned"
setcookie('session', 'value', [
    'secure' => true,
    'partitioned' => true,  // Nuevo en PHP 8.5
]);

// session_start() también lo soporta
session_start([
    'cookie_secure' => true,
    'cookie_partitioned' => true,
]);
```

## Deprecaciones en PHP 8.5

### 1. Backticks para Ejecución de Comandos

```php
// Deprecado:
$output = `ls -la`;

// Usar en su lugar:
$output = shell_exec('ls -la');
```

### 2. Casts No-Canónicos (NUEVO en 8.5)

```php
// Deprecado en PHP 8.5:
$x = (boolean) $val;  // ⚠️ usar (bool)
$x = (integer) $val;  // ⚠️ usar (int)
$x = (double) $val;   // ⚠️ usar (float)
$x = (binary) $val;   // ⚠️ usar (string)

// Correcto:
$x = (bool) $val;
$x = (int) $val;
$x = (float) $val;
$x = (string) $val;
```

### 3. Usar `null` como clave de array (NUEVO en 8.5)

```php
// Deprecado:
array_key_exists(null, $arr); // ⚠️ Warning

// Usar en su lugar:
array_key_exists('', $arr);
```

### 4. Métodos Mágicos `__sleep()` y `__wakeup()`

> ⚠️ **NOTA:** Esta migración es recomendada desde PHP 8.1 — `__sleep/__wakeup` siguen funcionando en PHP 8.5 pero es buena práctica migrar.

```php
// Reemplazar por (disponible desde PHP 8.1):
class Example {
    public function __serialize(): array { /* ... */ }
    public function __unserialize(array $data): void { /* ... */ }
}
```

### 3. Advertencias al Castear NAN

```php
$nan = NAN;
$int = (int) $nan;  // ⚠️ Warning en PHP 8.5
```

### 4. Desestructuración de Valores No-Array

```php
[$a, $b] = 'string';  // ⚠️ Warning en PHP 8.5
```

## Implementación en el Módulo de Clientes

Este documento describe cómo las características de PHP 8.5 están implementadas en el módulo CRUD de clientes.

## 1. Property Hooks

Property hooks allow you to define custom get/set behavior directly in property declarations.

### Implementation in `Coordinates.php`

```php
final readonly class Coordinates
{
    public function __construct(
        public ?float $latitude {
            set {
                if ($value !== null && ($value < -90 || $value > 90)) {
                    throw new \InvalidArgumentException('Latitude must be between -90 and 90');
                }
                $this->latitude = $value;
            }
        },
        public ?float $longitude {
            set {
                if ($value !== null && ($value < -180 || $value > 180)) {
                    throw new \InvalidArgumentException('Longitude must be between -180 and 180');
                }
                $this->longitude = $value;
            }
        }
    ) {}
}
```

**Benefits:**

- Automatic validation on property assignment
- No need for separate setter methods
- Cleaner, more declarative code

### Implementation in `SocialLinks.php`

```php
final readonly class SocialLinks
{
    public function __construct(
        public ?string $facebook {
            set => $this->facebook = $value !== null ? filter_var($value, FILTER_VALIDATE_URL) ? $value : null : null
        },
        // ... other properties
    ) {}
}
```

**Benefits:**

- URL validation happens automatically
- Invalid URLs are converted to null
- Type safety maintained

### Implementation in `Email.php`

```php
final readonly class Email
{
    public function __construct(
        public string $value {
            get => strtolower($this->value);
            set {
                $normalized = strtolower(trim($value));
                if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
                    throw new \InvalidArgumentException("Invalid email format: {$value}");
                }
                $this->value = $normalized;
            }
        }
    ) {}
}
```

**Benefits:**

- Email is always lowercase when accessed
- Validation on construction
- Normalization built-in

## 2. Pipe Operator (`|>`)

The pipe operator allows chaining function calls in a readable left-to-right manner.

### Implementation in `ClientDataTransformer.php`

```php
public static function transformForExport(ClientReadModel $client): array
{
    return $client
        |> self::extractBaseData(...)
        |> self::addFormattedDates(...)
        |> self::addSocialLinks(...)
        |> self::addCoordinates(...);
}
```

**Benefits:**

- More readable than nested function calls
- Clear data transformation pipeline
- Easy to add/remove transformation steps

### Sanitization Pipeline

```php
public static function sanitizeInput(array $input): array
{
    return $input
        |> self::trimStrings(...)
        |> self::normalizeUrls(...)
        |> self::validateCoordinates(...);
}
```

**Benefits:**

- Clear sanitization flow
- Each step is isolated and testable
- Easy to understand data flow

## 3. `#[\NoDiscard]` Attribute

This attribute warns when a function's return value is not used.

### Implementation

```php
#[\NoDiscard]
public static function sanitizeInput(array $input): array
{
    return $input
        |> self::trimStrings(...)
        |> self::normalizeUrls(...)
        |> self::validateCoordinates(...);
}
```

**Benefits:**

- Prevents accidental ignoring of sanitized data
- Compile-time warning if return value is discarded
- Better code safety

## 4. Clone with Modifications

While not directly implemented in this module, readonly classes can now be cloned with modifications:

```php
readonly class Client {
    public function __construct(
        public string $companyName,
        public string $email,
    ) {}
}

$client = new Client('ACME Corp', 'info@acme.com');
// ✅ Sintaxis correcta confirmada en PHP 8.5:
$updated = clone($client, ['email' => 'new@acme.com']);
// ⚠️ NOTA: La sintaxis `clone $obj with [...]` NO fue confirmada oficialmente.
// La forma correcta es clone($obj, ['prop' => value])
```

## Usage Examples

### Creating a Client with Validated Coordinates

```php
$coordinates = new Coordinates(
    latitude: 40.7128,  // ✓ Valid
    longitude: -74.0060 // ✓ Valid
);

// This will throw an exception:
$invalid = new Coordinates(
    latitude: 100,  // ✗ Invalid (> 90)
    longitude: 0
);
```

### Creating Social Links with URL Validation

```php
$socialLinks = new SocialLinks(
    facebook: 'https://facebook.com/company',  // ✓ Valid URL
    instagram: 'not-a-url',                    // ✗ Converted to null
    website: 'https://example.com'             // ✓ Valid URL
);
```

### Using the Data Transformer

```php
$client = $clientRepository->findById($id);
$exportData = ClientDataTransformer::transformForExport($client);

// Data flows through the pipeline:
// 1. Extract base data
// 2. Add formatted dates
// 3. Add social links
// 4. Add coordinates
```

## Migration Notes

### Before PHP 8.5

```php
// Manual validation in constructor
public function __construct(float $latitude, float $longitude)
{
    if ($latitude < -90 || $latitude > 90) {
        throw new \InvalidArgumentException('Invalid latitude');
    }
    $this->latitude = $latitude;
    // ...
}

// Nested function calls
$result = self::addCoordinates(
    self::addSocialLinks(
        self::addFormattedDates(
            self::extractBaseData($client)
        )
    )
);
```

### After PHP 8.5

```php
// Property hooks handle validation
public function __construct(
    public float $latitude {
        set {
            if ($value < -90 || $value > 90) {
                throw new \InvalidArgumentException('Invalid latitude');
            }
            $this->latitude = $value;
        }
    }
) {}

// Pipe operator for clarity
$result = $client
    |> self::extractBaseData(...)
    |> self::addFormattedDates(...)
    |> self::addSocialLinks(...)
    |> self::addCoordinates(...);
```

## Performance Considerations

- Property hooks have minimal overhead (similar to regular property access)
- Pipe operator is syntactic sugar, no runtime performance impact
- `#[\NoDiscard]` is a compile-time check, zero runtime cost

## Testing

When testing code with property hooks:

```php
test('coordinates validate latitude range', function () {
    expect(fn() => new Coordinates(latitude: 100, longitude: 0))
        ->toThrow(\InvalidArgumentException::class);
});

test('email is normalized to lowercase', function () {
    $email = new Email('TEST@EXAMPLE.COM');
    expect($email->value)->toBe('test@example.com');
});
```

## Consideraciones de Rendimiento

- **Operador Pipe:** Es azúcar sintáctico, sin impacto en el rendimiento en tiempo de ejecución
- **Property Hooks:** Overhead mínimo (similar al acceso regular a propiedades)
- **Extensión URI:** Usa bibliotecas nativas optimizadas (uriparser y Lexbor)
- **`#[\NoDiscard]`:** Verificación en tiempo de compilación, costo cero en runtime
- **Clone with:** Rendimiento similar a la clonación tradicional
- **Stack traces:** Impacto mínimo, solo se generan cuando ocurre un error fatal

## Mejoras de Seguridad

1. **Extensión URI:** Usa bibliotecas probadas y auditadas por investigadores de seguridad
2. **Validación de filtros:** Manejo de errores más robusto con excepciones
3. **Stack traces:** Mejor depuración en producción sin exponer información sensible
4. **Cookies particionadas:** Mejor aislamiento y privacidad

## Estrategia de Migración

### Paso 1: Actualizar PHP

```bash
# Verificar versión actual
php -v

# Actualizar a PHP 8.5
# (método depende del sistema operativo)
```

### Paso 2: Revisar Deprecaciones

- Reemplazar backticks con `shell_exec()`
- Migrar `__sleep()/__wakeup()` a `__serialize()/__unserialize()`
- Revisar casteos de NAN
- Verificar desestructuración de arrays

### Paso 3: Adoptar Nuevas Características

1. Identificar código con funciones anidadas → usar operador pipe
2. Reemplazar `parse_url()` → usar extensión URI
3. Simplificar métodos "wither" → usar clone with
4. Agregar `#[\NoDiscard]` a funciones críticas

### Paso 4: Testing

```bash
# Ejecutar suite de pruebas
php artisan test

# Verificar deprecaciones
php -d error_reporting=E_ALL script.php
```

## Compatibilidad con Versiones Anteriores

PHP 8.5 es una versión point release, por lo que la mayoría del código existente debería funcionar sin cambios. Sin embargo:

- Revisar las deprecaciones listadas arriba
- Probar exhaustivamente antes de desplegar en producción
- Considerar usar herramientas como PHPStan para detectar problemas

## Herramientas y Soporte

### IDEs

- **PHPStorm:** Soporte completo desde la versión 2025.3
- **VS Code:** Extensión PHP Intelephense soporta PHP 8.5
- **Vim/Neovim:** Plugins LSP actualizados

### Análisis Estático

- **PHPStan:** Soporte completo para PHP 8.5
- **Psalm:** Soporte desde versión 5.x
- **Rector:** Reglas de migración disponibles

### Testing

```php
// Ejemplo con Pest/PHPUnit
test('pipe operator transforms data correctly', function () {
    $result = 'Hello World'
        |> strtoupper(...)
        |> str_shuffle(...)
        |> trim(...);

    expect($result)->toBeString();
});

test('URI extension parses correctly', function () {
    $uri = Uri\Rfc3986\Uri::fromString('https://example.com/path');
    expect($uri->getScheme())->toBe('https');
    expect($uri->getHost())->toBe('example.com');
});
```

## Casos de Uso Reales

### 1. Pipeline de Procesamiento de Datos

```php
$processedData = $rawData
    |> $this->validate(...)
    |> $this->sanitize(...)
    |> $this->transform(...)
    |> $this->enrich(...)
    |> $this->persist(...);
```

### 2. Generación de Slugs

```php
$slug = $title
    |> trim(...)
    |> (fn($s) => preg_replace('/[^a-z0-9\s]/i', '', $s))
    |> (fn($s) => str_replace(' ', '-', $s))
    |> strtolower(...);
```

### 3. Validación de URLs

```php
use Uri\WhatWg\Url;

function validateAndNormalizeUrl(string $input): ?string {
    try {
        $url = new Url($input);
        return $url->toString();
    } catch (UriException $e) {
        return null;
    }
}
```

### 4. Objetos Inmutables con Clone With

```php
readonly class Money {
    public function __construct(
        public int $amount,
        public string $currency,
    ) {}

    public function add(Money $other): self {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException('Currency mismatch');
        }

        return clone $this with [
            'amount' => $this->amount + $other->amount,
        ];
    }
}
```

## Recursos y Referencias

### Documentación Oficial

- [PHP 8.5 Release Announcement](https://www.php.net/releases/8.5/en.php)
- [PHP 8.5 Migration Guide](https://www.php.net/manual/en/migration85.php)
- [Pipe Operator RFC](https://wiki.php.net/rfc/pipe-operator-v3)
- [URI Extension RFC](https://wiki.php.net/rfc/url_parsing_api)
- [Clone With RFC](https://wiki.php.net/rfc/clone_with_v2)

### Artículos y Tutoriales

- [PHP.Watch - PHP 8.5: What's New and Changed](https://php.watch/versions/8.5)
- [Kinsta - PHP 8.5: Here's what's new](https://kinsta.com/blog/php-8-5/)
- [Zend - PHP 8.5: New Features and Deprecations](https://www.zend.com/blog/php-8-5-features)

### Comunidad

- [PHP Foundation Blog](https://thephp.foundation/blog/)
- [PHP Internals Mailing List](https://externals.io/)
- [Reddit r/PHP](https://www.reddit.com/r/PHP/)

---

**Última actualización:** 2 de marzo de 2026  
**Versión del documento:** 2.0  
**Autor:** Documentación generada con información de fuentes oficiales de PHP

_Contenido rephraseado para cumplir con restricciones de licenciamiento. Consultar fuentes originales para información detallada._
