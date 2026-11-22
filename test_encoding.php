<?php
$data = file_get_contents('app/ValiantShade/Http/Controllers/NewsController.php');

// Let's find invalid UTF-8 sequences
// If we decode from Windows-1254 / ISO-8859-9 / ISO-8859-1 or fix invalid UTF-8:
// Let's inspect which parts are invalid UTF-8.
$len = strlen($data);
$fixed = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
echo "Initial valid: " . (mb_check_encoding($data, 'UTF-8') ? 'yes' : 'no') . PHP_EOL;

// Let's see what happens if we use utf8_encode on non-utf8 parts or convert from windows-1254/ISO-8859-9 to UTF-8
// But wait, the PHP file code itself was UTF-8. When fix_news_encoding ran:
// $fixedStr = mb_convert_encoding($str, 'ISO-8859-1', 'UTF-8');
// That took valid UTF-8 mojibake (e.g. C3 BC for ü represented as Ã¼ in ISO-8859-1) and converted it to ISO-8859-1 single byte (0xFC for ü)!
// So those Turkish characters became raw ISO-8859-1/Windows-1252 single bytes (0xFC, etc.), which are invalid UTF-8 in a UTF-8 file!
// To make them valid UTF-8, they need to be converted to UTF-8!
// Specifically, in ISO-8859-9 / Windows-1254 or ISO-8859-1:
$properUtf8 = mb_convert_encoding($data, 'UTF-8', 'ISO-8859-9');
// Wait! If the rest of the file was already valid UTF-8, converting the whole file from ISO-8859-9 to UTF-8 would double-encode the already-UTF-8 parts!

// So let's check:
echo "Properly convert only invalid byte sequences:" . PHP_EOL;
// Let's write a function that takes a string and converts any byte that is part of an invalid UTF-8 sequence from Windows-1254/ISO-8859-9 to UTF-8!
function fixInvalidUtf8($string) {
    // regex for valid UTF-8 byte sequences
    $regex = '/(
        [\x00-\x7F]+                        # ASCII
      | [\xC2-\xDF][\x80-\xBF]             # 2-byte UTF-8
      | [\xE0-\xEF][\x80-\xBF]{2}          # 3-byte UTF-8
      | [\xF0-\xF4][\x80-\xBF]{3}          # 4-byte UTF-8
    )/x';

    return preg_replace_callback('/.+?(?=' . $regex . '|$)/s', function ($matches) use ($regex) {
        $chunk = $matches[0];
        // Split chunk into valid UTF-8 matches and invalid bytes
        $result = '';
        $offset = 0;
        while ($offset < strlen($chunk)) {
            if (preg_match($regex, substr($chunk, $offset), $m, PREG_OFFSET_CAPTURE)) {
                $pos = $m[0][1];
                if ($pos > 0) {
                    $invalid = substr($chunk, $offset, $pos);
                    $result .= mb_convert_encoding($invalid, 'UTF-8', 'Windows-1254');
                }
                $result .= $m[0][0];
                $offset += $pos + strlen($m[0][0]);
            } else {
                $invalid = substr($chunk, $offset);
                $result .= mb_convert_encoding($invalid, 'UTF-8', 'Windows-1254');
                break;
            }
        }
        return $result;
    }, $data);
}

// Alternatively, let's test iconv or mb_convert_encoding with substitution, or better:
// Let's inspect git diff on NewsController.php!
