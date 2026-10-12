<?php
/**
 * Stoplist en espanol para el ranking TF-IDF del clasificador de temas.
 * Palabras normalizadas (minusculas, sin acentos). Se usa tanto al ajustar
 * el vocabulario como al vectorizar consultas: las palabras funcionales y los
 * verbos genericos no deben pesar en la similitud entre temas.
 *
 * Devuelve un arreglo plano; el modelo lo indexa por valor para busqueda O(1).
 */
return [
    'a', 'al', 'algo', 'algun', 'alguna', 'algunas', 'alguno', 'algunos', 'alli', 'alla',
    'ante', 'antes', 'aqui', 'asi', 'aun', 'aunque', 'bien', 'buen', 'buena', 'buenas',
    'bueno', 'buenos', 'cada', 'casi', 'como', 'con', 'contra', 'cual', 'cuales', 'cuando',
    'cuanto', 'de', 'del', 'desde', 'donde', 'dos', 'el', 'ella', 'ellas', 'ellos', 'en',
    'entre', 'era', 'eran', 'eres', 'es', 'esa', 'esas', 'ese', 'eso', 'esos', 'esta',
    'estaba', 'estado', 'estan', 'estar', 'estas', 'este', 'esto', 'estos', 'estoy',
    'estamos', 'fue', 'fueron', 'ha', 'hace', 'hacen', 'hacer', 'hacia', 'han', 'hasta',
    'hay', 'la', 'las', 'le', 'les', 'lo', 'los', 'mas', 'me', 'mi', 'mientras', 'mis',
    'mucho', 'muchos', 'muy', 'nada', 'ni', 'no', 'nos', 'nosotros', 'nuestra', 'nuestro',
    'o', 'os', 'otra', 'otras', 'otro', 'otros', 'para', 'pero', 'poco', 'por', 'porque',
    'que', 'quien', 'quienes', 'se', 'sea', 'segun', 'ser', 'si', 'sin', 'sobre', 'solo',
    'son', 'soy', 'su', 'sus', 'tal', 'tambien', 'tan', 'te', 'tener', 'tengo', 'ti',
    'tiene', 'tienen', 'todo', 'toda', 'todos', 'tu', 'tus', 'un', 'una', 'uno', 'unos',
    'vos', 'ya', 'yo', 'me', 'mae', 'oye', 'oigan', 'hola', 'gracias', 'favor', 'saludos',
    'dia', 'dias', 'noche', 'noches', 'manana', 'ayer', 'hoy', 'ahora', 'despues',
    'siempre', 'nunca', 'jamas', 'vamos', 'van', 'va', 'voy', 'ir', 'fui', 'sido',
    'gusta', 'gustan', 'encanta', 'encantan', 'quiero', 'quiere', 'queremos', 'quieren',
    'puedo', 'puede', 'podemos', 'pueden', 'cosa', 'cosas', 'gente', 'alguien', 'nadie',
    'ninguno', 'ambos', 'resto', 'finde', 'semana', 'tiempo', 'hora', 'veces', 'vez',
    'poquito', 'bastante', 'demasiado', 'super', 'mega', 'rico', 'rica', 'buenisimo',
    'buenisima', 'jaja', 'jajaja', 'jajajaja', 'di', 'dice', 'dicen', 'decir', 'este',
    'fiesta', 'disco', 'concierto', 'playlist', 'cancion', 'banda', 'musica', 'tema',
    'comer', 'almorzar', 'cenar', 'desayunar', 'dormir', 'caminar', 'rato', 'temprano',
    'pelicula', 'gusto', 'rato', 'nada', 'rato', 'ver', 'vieras', 'vieran',
];
