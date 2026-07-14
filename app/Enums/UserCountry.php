<?php

namespace App\Enums;

enum UserCountry: string
{
    // Глобальный/Дефолтный вариант
    case Global = 'global';

    // Кавказ и СНГ
    case Azerbaijan = 'az';
    case Georgia = 'ge';
    case Armenia = 'am';
    case Russia = 'ru';
    case Kazakhstan = 'kz';
    case Uzbekistan = 'uz';
    case Ukraine = 'ua';
    case Belarus = 'by';
    case Kyrgyzstan = 'kg';
    case Tajikistan = 'tj';
    case Moldova = 'md';

    // Ближний Восток и Турция
    case Turkey = 'tr';
    case Iran = 'ir';
    case Iraq = 'iq';
    case UAE = 'ae';
    case SaudiArabia = 'sa';
    case Israel = 'il';

    // Европа
    case Germany = 'de';
    case UnitedKingdom = 'gb';
    case France = 'fr';
    case Italy = 'it';
    case Spain = 'es';
    case Poland = 'pl';
    case Netherlands = 'nl';
    case Romania = 'ro';
    case Czechia = 'cz';
    case Sweden = 'se';
    case Switzerland = 'ch';
    case Norway = 'no';
    case Finland = 'fi';

    // Азия и Америка
    case USA = 'us';
    case Canada = 'ca';
    case China = 'cn';
    case Japan = 'jp';
    case SouthKorea = 'kr';
    case India = 'in';
    case Brazil = 'br';

    /**
     * Возвращает Emoji-флаг для каждого кода страны
     */
/**
     * Возвращает URL векторного SVG-флага для каждого кода страны
     */
    public static function getFlag(string $code): string
    {
        $code = strtolower(trim($code));
        
        // Если это глобальный поиск, возвращаем иконку земного шара
        if ($code === 'global') {
            return 'https://cdnjs.cloudflare.com/ajax/libs/twemoji/14.0.2/svg/1f30e.svg';
        }

        // Защитный список поддерживаемых нами стран (соответствует нашему Enum)
        $supported = [
            'az', 'ge', 'am', 'ru', 'kz', 'uz', 'ua', 'by', 'kg', 'tj', 'md',
            'tr', 'ir', 'iq', 'ae', 'sa', 'il',
            'de', 'gb', 'fr', 'it', 'es', 'pl', 'nl', 'ro', 'cz', 'se', 'ch', 'no', 'fi',
            'us', 'ca', 'cn', 'jp', 'kr', 'in', 'br'
        ];

        if (in_array($code, $supported)) {
            // Используем стабильный CDN с флагами в формате 4x3 (или 1x1 при желании)
            return "https://cdnjs.cloudflare.com/ajax/libs/flag-icon-css/3.5.0/flags/4x3/{$code}.svg";
        }

        // Дефолтный фолбэк (Земной шар)
        return 'https://cdnjs.cloudflare.com/ajax/libs/twemoji/14.0.2/svg/1f30e.svg';
    }
}