<?php

declare(strict_types=1);

namespace Support\Stemmer;

use Illuminate\Support\Str;
use TeamTNT\TNTSearch\Stemmer\Stemmer;
use voku\helper\ASCII;

/**
 * Based on https://github.com/jedi-pl/php-polish-stemmer and https://github.com/Tutanchamon/pl_stemmer.
 */
final class Polish implements Stemmer
{
    public const VOWELS = ['a', 'i', 'e', 'o', 'u', 'y'];

    public const SUFFIXES = ['dziesiatko', 'dziesiatce', 'dziesiatka', 'dziesiatke', 'dziesiatki', 'ynascioro', 'anascioro', 'enascioro', 'nascioro', 'dziesiat', 'dziescia', 'dziesci', 'nastke', 'nastka', 'nastki', 'iescie', 'nastko', 'nascie', 'nastce', 'iunio', 'uszek', 'iuset', 'escie', 'eczka', 'yczek', 'eczko', 'iczek', 'eczek', 'setka', 'iema', 'iemu', 'ysta', 'ioma', 'owie', 'owym', 'iego', 'usia', 'giem', 'unia', 'iami', 'unio', 'usui', 'ecie', 'owi', 'ych', 'szy', 'esz', 'asz', 'isz', 'ema', 'emu', 'óch', 'ymi', 'ami', 'set', 'ich', 'ech', 'iom', 'imi', 'ach', 'oma', 'owa', 'owe', 'owy', 'iem', 'ego', 'cie', 'ym', 'um', 'yk', 'om', 'ow', 'us', 'im', 'om', 'ek', 'ej', 'ga', 'gu', 'ia', 'ie', 'aj', 'ik', 'iu', 'ka', 'ki', 'ko', 'mi', 'em', 'ce', 'a', 'e', 'i', 'y', 'o', 'u'];

    public const STOPWORDS = ['aby', 'albo', 'ale', 'ani', 'az', 'aczkolwiek', 'bardzo', 'beda', 'bedzie', 'bez', 'bo', 'bowiem', 'by', 'byc', 'byl', 'byla', 'byli', 'bylo', 'byly', 'bym', 'chce', 'choc', 'co', 'coraz', 'cos', 'czesto', 'czy', 'czyli', 'dla', 'do', 'dr', 'gdy', 'gdyby', 'gdyz', 'gdzie', 'go', 'godz', 'hab', 'i', 'ich', 'ii', 'iii', 'im', 'inne', 'inz', 'iv', 'ix', 'iz', 'ja', 'jak', 'jakie', 'jako', 'je', 'jednak', 'jednym', 'jedynie', 'jego', 'jej', 'jesli', 'jest', 'jeszcze', 'jezeli', 'juz', 'kiedy', 'kilku', 'kto', 'ktora', 'ktore', 'ktorego', 'ktorej', 'ktory', 'ktorych', 'ktorym', 'ktorzy', 'lat', 'lecz', 'lub', 'ma', 'maja', 'mamy', 'mgr', 'mi', 'mial', 'mimo', 'mnie', 'moga', 'moze', 'mozna', 'mu', 'musi', 'na', 'nad', 'nam', 'nas', 'nawet', 'nic', 'nich', 'nie', 'niej', 'nim', 'niz', 'no', 'nowe', 'np', 'nr', 'o', 'od', 'ok', 'on', 'one', 'o.o.', 'oraz', 'pan', 'pl', 'po', 'pod', 'ponad', 'poniewaz', 'poza', 'prof', 'przed', 'przede', 'przez', 'przy', 'raz', 'razie', 'roku', 'rowniez', 'sa', 'sie', 'sobie', 'sposob', 'swoje', 'ta', 'tak', 'takich', 'takie', 'takze', 'tam', 'te', 'tego', 'tej', 'tel', 'temu', 'ten', 'teraz', 'tez', 'to', 'trzeba', 'tu', 'tych', 'tylko', 'tym', 'tys', 'tzw', 'u', 'ul', 'vi', 'vii', 'viii', 'vol', 'w', 'we', 'wie', 'wiec', 'wlasnie', 'wsrod', 'wszystko', 'www', 'xi', 'xii', 'xiii', 'xiv', 'xv', 'z', 'za', 'zas', 'ze', 'zl'];

    public static string $encoding = 'UTF-8';

    /**
     * @param string $word
     *
     * @return string
     */
    public static function stem($word)
    {
        $filtered_word = self::filter($word);
        if ($filtered_word !== null) {
            $filtered_word = self::removePrefixes($filtered_word);

            return self::removeSuffixes($filtered_word);
        }

        return $word;
    }

    private static function filter(string $word): ?string
    {
        $filtered_word = Str::ascii(Str::lower($word), ASCII::POLISH_LANGUAGE_CODE);
        if (Str::length($filtered_word, self::$encoding) > 0 && ctype_alpha($filtered_word)) {
            if (!in_array($filtered_word, self::STOPWORDS, true)) {
                return $filtered_word;
            }
        }

        return null;
    }

    private static function removePrefixes(string $filtered_word): string
    {
        $word_length = Str::length($filtered_word, self::$encoding);
        if ($word_length > 7 && (Str::substr($filtered_word, 0, 3) === 'naj') && Str::endsWith($filtered_word, ['sze', 'szy', 'szych', 'ej'])) {
            return Str::substr($filtered_word, 3, -3);
        }

        return $filtered_word;
    }

    private static function removeSuffixes(string $filtered_word): string
    {
        $word_length = Str::length($filtered_word, self::$encoding);
        foreach (self::SUFFIXES as $suffix) {
            $suffix_length = Str::length($suffix, self::$encoding);
            if ($suffix_length > $word_length) {
                continue;
            }
            if (($word_length - $suffix_length) >= 3) {
                $last_part = Str::substr($filtered_word, -$suffix_length, null);
                if ($last_part === $suffix) {
                    $one_before_last_part = Str::substr($filtered_word, -$suffix_length - 1, 1);
                    if (!in_array($one_before_last_part, self::VOWELS, true)) {
                        return Str::substr($filtered_word, 0, $word_length - $suffix_length);
                    }
                }
            }
        }

        return $filtered_word;
    }
}
