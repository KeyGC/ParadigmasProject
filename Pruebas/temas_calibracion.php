<?php
// CALIBRACION DE TEMAS: php Pruebas/temas_calibracion.php
// Conjunto (a): >=40 mensajes benignos/genericos -> se reportan FALSOS POSITIVOS
// Conjunto (b): >=40 mensajes tematicos nuevos (no del dataset) -> ACIERTOS
// Criterio de aceptacion: 0 falsos positivos en (a) y >=60% aciertos en (b).
// Requiere .env.local exportado.

require_once __DIR__ . '/../Configuracion/configuracion.php';
require_once APP_PATH . '/Modelo/temasmodelo.php';

header('Content-Type: text/plain; charset=utf-8');

// (a) Benignos/genericos: NO deberian clasificar.
$benignos = [
    'me encanta bailar',
    'vamos a comer',
    'hoy entrené',
    'qué cansancio',
    'hola, cómo están todos',
    'buenas tardes, qué tal',
    'jaja sí, muy cierto',
    'no sé, tal vez mañana',
    'alguien por ahí?',
    'estoy un poco cansado hoy',
    'qué frío está haciendo estos días',
    'mae, todo bien?',
    'nos vemos luego, cuídense',
    'gracias por la info',
    'buen día para todos',
    'jajaja me hicieron reír',
    'a mí me pasa lo mismo',
    'eso mismo pensé yo',
    'pura vida, todo tranquilo',
    'que tengan buena semana',
    'estoy en la casa sin hacer nada',
    'oye, una pregunta rápida',
    'qué hace el resto este finde',
    'me siento muy bien hoy',
    'a qué hora nos vemos',
    'no entiendo nada de eso',
    'qué bueno verte por aquí',
    'estuvo largo el día',
    'me voy a dormir temprano',
    'mañana tengo que madrugar',
    'tengo muchas cosas que hacer',
    'qué opinan ustedes',
    'jaja qué locura',
    'no me acuerdo bien',
    'todo bien por dicha',
    'salimos a caminar un rato',
    'me gusto mucho la película',
    'quedó bonito el atardecer',
    'qué rico conversar con ustedes',
    'al final no fui',
    'me contaron una historia graciosa',
    'estoy pensando en comprar algo',
    'ya casi termino lo que estaba haciendo',
];

// (b) Tematicos nuevos (fuera del dataset), con tema esperado.
$tematicos = [
    'escucho mucho pop en la radio' => 'Pop',
    'me gusta el rock nacional' => 'Rock',
    'ponme reggaetón para animarme' => 'Reggaetón',
    'quiero aprender pasos de salsa' => 'Salsa',
    'el techno me encanta' => 'Electrónica',
    'escucho jazz los domingos' => 'Jazz',
    'soy fan del hip hop' => 'Hip Hop',
    'estoy escribiendo rap' => 'Rap',
    'me gusta el trap' => 'Trap',
    'el reggae tiene buen mensaje' => 'Reggae',
    'me gusta el country' => 'Country',
    'escucho metal pesado' => 'Metal',
    'bailo bachata los viernes' => 'Bachata',
    'me gusta el merengue' => 'Merengue',
    'la cumbia me alegra el día' => 'Cumbia',
    'me relaja la música clásica' => 'Clásica',
    'pon música funk' => 'Funk',
    'me gusta el punk' => 'Punk',
    'escucho bandas indie' => 'Indie',
    'quiero comer tacos mexicanos' => 'Mexicana',
    'preparo pasta italiana' => 'Italiana',
    'me encanta el sushi' => 'Japonesa',
    'pedimos comida china' => 'China',
    'cociné curry tailandés' => 'Tailandesa',
    'comida india con especias' => 'India',
    'me gusta la dieta mediterránea' => 'Mediterránea',
    'soy vegano' => 'Vegana',
    'postres de chocolate' => 'Postres',
    'quiero una hamburguesa con papas' => 'Comida rápida',
    'comimos mariscos frescos' => 'Mariscos',
    'hicimos un asado a la parrilla' => 'Parrilla',
    'pidamos pizza con extra queso' => 'Pizza',
    'voy por un café con leche' => 'Café y brunch',
    'preparo una ensalada saludable' => 'Saludable',
    'quiero gallo pinto costarricense' => 'Costarricense',
    'jugamos fútbol mañana' => 'Fútbol',
    'encesto en baloncesto' => 'Baloncesto',
    'tomo clases de yoga' => 'Yoga',
    'salí en bicicleta al trabajo' => 'Ciclismo',
    'fui a trotar al parque' => 'Running',
    'practico karate' => 'Artes marciales',
    'voy a nadar a la piscina' => 'Natación',
    'hice la rutina de crossfit' => 'Crossfit',
    'hice senderismo en la montaña' => 'Senderismo',
    'jugué tenis ayer' => 'Tenis',
    'voleibol en la playa' => 'Voleibol',
    'entreno fuerza en el gimnasio' => 'Entrenamiento de fuerza',
    'me gusta el skate' => 'Skateboarding',
    'voy a surfear el sábado' => 'Surf',
    'quiero escalar roca' => 'Escalada',
];

$modelo = new TemasModelo();

if (!$modelo->estaDisponible()) {
    echo "Artefacto de temas no disponible. Corra primero sembrartemas.php --reconstruir\n";
    exit(1);
}

echo "TEMAS_UMBRAL actual: " . TEMAS_UMBRAL . PHP_EOL;
echo "Benignos: " . count($benignos) . " | Tematicos: " . count($tematicos) . PHP_EOL;

// (a) falsos positivos
$falsosPositivos = [];
foreach ($benignos as $texto) {
    $r = $modelo->clasificarTema($texto);
    if ($r['exito'] && !empty($r['clasificado'])) {
        $falsosPositivos[] = sprintf('%s  ->  %s (%.3f)', $texto, $r['tema'], $r['score']);
    }
}

// (b) aciertos
$aciertos = 0;
$fallos = [];
foreach ($tematicos as $texto => $esperado) {
    $r = $modelo->clasificarTema($texto);
    if ($r['exito'] && !empty($r['clasificado']) && $r['tema'] === $esperado) {
        $aciertos++;
    } else {
        $obtenido = ($r['exito'] && !empty($r['clasificado'])) ? $r['tema'] : ($r['motivo'] ?? ($r['mensaje'] ?? 'error'));
        $fallos[] = sprintf('esperado=%-22s obtenido=%-22s  %s', $esperado, $obtenido, $texto);
    }
}

echo PHP_EOL . '=== (a) FALSOS POSITIVOS: ' . count($falsosPositivos) . ' ===' . PHP_EOL;
foreach ($falsosPositivos as $f) {
    echo '  ' . $f . PHP_EOL;
}

$totalTem = count($tematicos);
$tasa = $totalTem > 0 ? ($aciertos / $totalTem) : 0;

echo PHP_EOL . '=== (b) ACIERTOS: ' . $aciertos . '/' . $totalTem . ' (' . round($tasa * 100, 1) . '%) ===' . PHP_EOL;
foreach ($fallos as $f) {
    echo '  ' . $f . PHP_EOL;
}

$ok = count($falsosPositivos) === 0 && $tasa >= 0.60;
echo PHP_EOL . str_repeat('=', 60) . PHP_EOL;
echo 'Criterio (0 FP en a, >=60% en b): ' . ($ok ? 'CUMPLE' : 'NO CUMPLE') . PHP_EOL;
