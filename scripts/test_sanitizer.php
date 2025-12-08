<?php

/**
 * Script de teste para App\Utils\Sanitizer
 * 
 * Testa todos os métodos de sanitização para garantir que funcionam corretamente.
 */

require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\Sanitizer;

echo "🧪 TESTANDO SANITIZER\n";
echo str_repeat("=", 80) . "\n\n";

$tests = [];
$passed = 0;
$failed = 0;

/**
 * Função auxiliar para executar testes
 */
function test(string $name, callable $test): void
{
    global $tests, $passed, $failed;
    
    try {
        $result = $test();
        if ($result === true) {
            echo "✅ {$name}\n";
            $passed++;
        } else {
            echo "❌ {$name}\n";
            echo "   Esperado: true, Recebido: " . var_export($result, true) . "\n";
            $failed++;
        }
    } catch (\Exception $e) {
        echo "❌ {$name}\n";
        echo "   Erro: " . $e->getMessage() . "\n";
        $failed++;
    }
    
    $tests[] = $name;
}

echo "📋 Testando método string()\n";
echo str_repeat("-", 80) . "\n";

test("string() - String válida", function() {
    $result = Sanitizer::string("  Teste  ");
    return $result === "Teste";
});

test("string() - String com HTML", function() {
    $result = Sanitizer::string("<script>alert('xss')</script>");
    return $result === "&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;";
});

test("string() - String muito longa", function() {
    $long = str_repeat("a", 300);
    $result = Sanitizer::string($long, 255);
    return strlen($result) === 255;
});

test("string() - Null retorna null", function() {
    return Sanitizer::string(null) === null;
});

test("string() - String vazia retorna null", function() {
    return Sanitizer::string("   ") === null;
});

test("string() - Sem escape HTML quando solicitado", function() {
    $result = Sanitizer::string("<b>teste</b>", 255, false);
    return $result === "<b>teste</b>";
});

echo "\n📋 Testando método email()\n";
echo str_repeat("-", 80) . "\n";

test("email() - Email válido", function() {
    $result = Sanitizer::email("  teste@exemplo.com  ");
    return $result === "teste@exemplo.com";
});

test("email() - Email inválido retorna null", function() {
    return Sanitizer::email("email-invalido") === null;
});

test("email() - Null retorna null", function() {
    return Sanitizer::email(null) === null;
});

test("email() - String vazia retorna null", function() {
    return Sanitizer::email("   ") === null;
});

echo "\n📋 Testando método int()\n";
echo str_repeat("-", 80) . "\n";

test("int() - Inteiro válido", function() {
    return Sanitizer::int("123") === 123;
});

test("int() - Inteiro com min/max", function() {
    $result = Sanitizer::int(50, 1, 100);
    return $result === 50;
});

test("int() - Inteiro abaixo do mínimo retorna null", function() {
    return Sanitizer::int(0, 1, 100) === null;
});

test("int() - Inteiro acima do máximo retorna null", function() {
    return Sanitizer::int(200, 1, 100) === null;
});

test("int() - String não numérica retorna null", function() {
    return Sanitizer::int("abc") === null;
});

test("int() - Null retorna null", function() {
    return Sanitizer::int(null) === null;
});

echo "\n📋 Testando método float()\n";
echo str_repeat("-", 80) . "\n";

test("float() - Float válido", function() {
    return Sanitizer::float("123.45") === 123.45;
});

test("float() - Float com min/max", function() {
    $result = Sanitizer::float(50.5, 1.0, 100.0);
    return $result === 50.5;
});

test("float() - Float abaixo do mínimo retorna null", function() {
    return Sanitizer::float(0.5, 1.0, 100.0) === null;
});

test("float() - Float acima do máximo retorna null", function() {
    return Sanitizer::float(200.5, 1.0, 100.0) === null;
});

echo "\n📋 Testando método url()\n";
echo str_repeat("-", 80) . "\n";

test("url() - URL válida", function() {
    $result = Sanitizer::url("  https://exemplo.com  ");
    return $result === "https://exemplo.com";
});

test("url() - URL inválida retorna null", function() {
    return Sanitizer::url("não é uma url") === null;
});

test("url() - Null retorna null", function() {
    return Sanitizer::url(null) === null;
});

echo "\n📋 Testando método phone()\n";
echo str_repeat("-", 80) . "\n";

test("phone() - Telefone válido", function() {
    $result = Sanitizer::phone("(11) 98765-4321");
    return $result === "(11) 98765-4321";
});

test("phone() - Telefone com caracteres inválidos", function() {
    $result = Sanitizer::phone("(11) 98765-4321@#$");
    return $result === "(11) 98765-4321";
});

test("phone() - Null retorna null", function() {
    return Sanitizer::phone(null) === null;
});

test("phone() - String vazia retorna null", function() {
    return Sanitizer::phone("   ") === null;
});

echo "\n📋 Testando método document()\n";
echo str_repeat("-", 80) . "\n";

test("document() - Documento válido", function() {
    $result = Sanitizer::document("123.456.789-00");
    return $result === "12345678900";
});

test("document() - Documento com caracteres especiais", function() {
    $result = Sanitizer::document("12.345.678/0001-90");
    return $result === "12345678000190";
});

test("document() - Null retorna null", function() {
    return Sanitizer::document(null) === null;
});

echo "\n📋 Testando método text()\n";
echo str_repeat("-", 80) . "\n";

test("text() - Texto válido", function() {
    $result = Sanitizer::text("  Texto   com   espaços  ");
    return $result === "Texto com espaços";
});

test("text() - Texto sem escape HTML", function() {
    $result = Sanitizer::text("<b>teste</b>");
    return $result === "<b>teste</b>";
});

test("text() - String vazia retorna string vazia", function() {
    return Sanitizer::text("   ") === "";
});

echo "\n📋 Testando método slug()\n";
echo str_repeat("-", 80) . "\n";

test("slug() - Slug válido", function() {
    $result = Sanitizer::slug("  Meu Artigo  ");
    return $result === "meu-artigo";
});

test("slug() - Slug com acentos", function() {
    $result = Sanitizer::slug("Artigo com Acentos");
    return $result === "artigo-com-acentos";
});

test("slug() - Slug com caracteres especiais", function() {
    $result = Sanitizer::slug("Artigo@#$%Especial!");
    return $result === "artigoespecial";
});

test("slug() - Null retorna null", function() {
    return Sanitizer::slug(null) === null;
});

echo "\n📋 Testando método bool()\n";
echo str_repeat("-", 80) . "\n";

test("bool() - Boolean true", function() {
    return Sanitizer::bool(true) === true;
});

test("bool() - Boolean false", function() {
    return Sanitizer::bool(false) === false;
});

test("bool() - String 'true'", function() {
    return Sanitizer::bool("true") === true;
});

test("bool() - String 'false'", function() {
    return Sanitizer::bool("false") === false;
});

test("bool() - Número 1", function() {
    return Sanitizer::bool(1) === true;
});

test("bool() - Número 0", function() {
    return Sanitizer::bool(0) === false;
});

test("bool() - String inválida retorna null", function() {
    return Sanitizer::bool("invalido") === null;
});

echo "\n📋 Testando método stringArray()\n";
echo str_repeat("-", 80) . "\n";

test("stringArray() - Array válido", function() {
    $result = Sanitizer::stringArray(["  teste1  ", "  teste2  "]);
    return $result === ["teste1", "teste2"];
});

test("stringArray() - Array com muitos itens retorna null", function() {
    $array = array_fill(0, 101, "teste");
    return Sanitizer::stringArray($array, 255, 100) === null;
});

test("stringArray() - Null retorna null", function() {
    return Sanitizer::stringArray(null) === null;
});

test("stringArray() - Não array retorna null", function() {
    return Sanitizer::stringArray("não é array") === null;
});

echo "\n";
echo str_repeat("=", 80) . "\n";
echo "📊 RESUMO DOS TESTES\n";
echo str_repeat("=", 80) . "\n";
echo "Total de testes: " . count($tests) . "\n";
echo "✅ Passou: {$passed}\n";
echo "❌ Falhou: {$failed}\n";
echo "\n";

if ($failed === 0) {
    echo "🎉 Todos os testes passaram!\n";
    exit(0);
} else {
    echo "⚠️  Alguns testes falharam. Verifique os erros acima.\n";
    exit(1);
}

