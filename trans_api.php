<?php

/**************************************************************
"Learning with Texts" (LWT) is free and unencumbered software 
released into the PUBLIC DOMAIN.

Anyone is free to copy, modify, publish, use, compile, sell, or
distribute this software, either in source code form or as a
compiled binary, for any purpose, commercial or non-commercial,
and by any means.

In jurisdictions that recognize copyright laws, the author or
authors of this software dedicate any and all copyright
interest in the software to the public domain. We make this
dedication for the benefit of the public at large and to the 
detriment of our heirs and successors. We intend this 
dedication to be an overt act of relinquishment in perpetuity
of all present and future rights to this software under
copyright law.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE 
WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE
AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS BE LIABLE 
FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN 
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN 
THE SOFTWARE.

For more information, please refer to [http://unlicense.org/].
***************************************************************/

/**************************************************************
Call: trans_api.php?from=...&dest=...&phrase=...
      ... from=L2 language code (see trans)
      ... dest=L1 language code (see trans)
      ... phrase=... word or expression to be translated by 
                     trans (Translate Shell, see
                     https://github.com/soimort/translate-shell )
***************************************************************
Example trans calls: 
>> trans -b zh:en "這本關於中國歷史的書一定很有意思。"
This book on Chinese history must be very interesting.
>> trans -b :@zh "這本關於中國歷史的書一定很有意思。"
Zhè běn guānyú zhōngguó lìshǐ de shū yīdìng hěn yǒuyìsi.
>> trans -b zh:en "有意思"
interesting
>> trans -b :@zh "有意思"
Yǒuyìsi
>> trans -b fr:de "Le nombre de patients hospitalisés augmente lentement mais sûrement."
Die Zahl der Krankenhauspatienten nimmt langsam aber sicher zu.
>> trans -b de:en "Die Zahl der Krankenhauspatienten nimmt langsam aber sicher zu."
The number of hospital patients is slowly but surely increasing.
>> trans -b en:fr "The number of hospital patients is slowly but surely increasing."
Le nombre de patients hospitalisés augmente lentement mais sûrement.
>> trans -b ja:en "日本語を話しますか。"
Do you speak Japanese.
>> trans -b :@ja "日本語を話しますか。"
Nihongo o hanashimasu ka.
>> trans -b th:en "สวัสดีตอนเช้า"
Good morning
>> trans -b :@th "สวัสดีตอนเช้า"
S̄wạs̄dī txn chêā
>> trans -no-ansi -indent 0 -show-prompt-message n -show-dictionary n -show-languages n -show-alternatives n fr:de "acheter"
acheter

Kaufen
>> trans -no-ansi -indent 0 -show-prompt-message n -show-dictionary n -show-languages n -show-alternatives n zh:en "他学的是新闻"
他学的是新闻
(Tā xué de shì xīnwén)

He studied journalism
***************************************************************/

require_once( 'settings.inc.php' );
require_once( 'connect.inc.php' );
require_once( 'dbutils.inc.php' );
require_once( 'utilities.inc.php' );

$from = trim(stripTheSlashesIfNeeded($_REQUEST["from"]));
$dest = trim(stripTheSlashesIfNeeded($_REQUEST["dest"]));
$phrase = mb_strtolower(trim(stripTheSlashesIfNeeded($_REQUEST["phrase"])), 'UTF-8');
$ok = FALSE;

pagestart_nobody('');
$titletext = '<a target="_blank" href="https://github.com/soimort/translate-shell">Translate Shell (' . tohtml($from) . " : " . tohtml($dest) . "):  &nbsp; <span class=\"red2\">" . tohtml($phrase) . "</span></a>";
echo '<h3>' . $titletext . '</h3>';

if (! isset($transpath)) {
	echo '<p>Variable $transpath (= path to <i>Translate Shell</i> executable) is NOT DEFINED in file <i>connect.inc.php</i>.<br />Aborted.</p>';
	pageend();
	exit;
}

$PATH_TO_TRANS = trim($transpath);

if (! file_exists($PATH_TO_TRANS)) {
	echo '<p><i>Translate Shell</i> executable <i>' . $PATH_TO_TRANS . '</i> does NOT EXIST<br />(we are in directory <i>' . getcwd() . '</i>).<br />Aborted.</p>';
	pageend();
	exit;
}

if (! is_executable($PATH_TO_TRANS)) {
	echo '<p><i>Translate Shell</i> executable <i>' . $PATH_TO_TRANS . '</i> is NOT EXECUTABLE<br />(we are in directory <i>' . getcwd() . '</i>).<br />Aborted.</p>';
	pageend();
	exit;
}

?>
<script type="text/javascript">
//<![CDATA[
function addTranslation (s) {
	var w = window.parent.frames['ro'];
	if (typeof w == 'undefined') w = window.opener;
	if (typeof w == 'undefined') {
		alert ('Translation can not be copied!');
		return;
	}
	var c = w.document.forms[0].WoTranslation;
	if (typeof c != 'object') {
		alert ('Translation can not be copied!');
		return;
	}
	var oldValue = c.value;
	if (oldValue.trim() == '') {
		c.value = s;
		w.makeDirty();
	}
	else {
		if (oldValue.indexOf(s) == -1) {
			c.value = oldValue + ' / ' + s;
			w.makeDirty();
		}
		else {
			if (confirm('"' + s + '" seems already to exist as a translation.\nInsert anyway?')) { 
				c.value = oldValue + ' / ' + s;
				w.makeDirty();
			}
		}
	}
}

function addRomanization (s) {
	var w = window.parent.frames['ro'];
	if (typeof w == 'undefined') w = window.opener;
	if (typeof w == 'undefined') {
		alert ('Romanization can not be copied!');
		return;
	}
	var c = w.document.forms[0].WoRomanization;
	if (typeof c != 'object') {
		alert ('Romanization can not be copied!');
		return;
	}
	var oldValue = c.value;
	if (oldValue.trim() == '') {
		c.value = s;
		w.makeDirty();
	}
	else {
		if (oldValue.indexOf(s) == -1) {
			c.value = oldValue + ' / ' + s;
			w.makeDirty();
		}
		else {
			if (confirm('"' + s + '" seems already to exist as a romanization.\nInsert anyway?')) { 
				c.value = oldValue + ' / ' + s;
				w.makeDirty();
			}
		}
	}
}
//]]>
</script>
<?php

if ($from != '' && $dest != '' && $phrase != '') {
	$translation = shell_exec($PATH_TO_TRANS . ' -no-ansi -indent 0 -show-prompt-message n -show-dictionary n -show-languages n -show-alternatives n ' . $from . ':' .
		$dest . ' "' . $phrase . '"');
	if (is_null($translation)) {
		$translation = '[ERROR]';
	} else {
		$translation = trim($translation);
		if ($translation == '') {
			$translation = '[ERROR]';
		} else {
			$translation = str_replace($phrase . "\n", "", $translation);
			$translation = str_replace("\n\n", "\n", $translation);
			while (substr($translation,0,1) == "\n") 
				$translation = substr($translation,1);
			$translation = str_replace("\n", "§", $translation);
			preg_match('#^\((.*)\)§(.*)$#', $translation, $match);
			if (count($match) > 2) {
				$romanization = mb_strtolower($match[1]);
				$translation = $match[2];
			} else {
				$romanization = '';
			}
		}
	}
	$ok = ($translation != '[ERROR]');
} else {
	echo '<p>Some parameters are missing (language code(s) and/or word).<p />';
	pageend();
	exit;
}

if ( $ok ) {
	echo '<p>(Click on <img src="icn/tick-button.png" title="Choose" alt="Choose" /> to copy into above term)</p>';
	echo '<p><span class="click" onclick="addTranslation(' . 
		prepare_textdata_js($translation) . ');">' .
		'<img src="icn/tick-button.png" title="Copy" alt="Copy" />' .
		' &nbsp; <b>Translation:</b></span> ' . 
		$translation . '</p>';
	if ($romanization != '') {
		echo '<p><span class="click" onclick="{ addTranslation(' . 
			prepare_textdata_js($translation) . '); addRomanization(' . 
			prepare_textdata_js($romanization) . '); }">' .
			'<img src="icn/tick-button.png" title="Copy" alt="Copy" />' .
			' &nbsp; <b>↑ Translation and ↓ Romanization</b></span></p>';
		echo '<p><span class="click" onclick="addRomanization(' . 
			prepare_textdata_js($romanization) . ');">' .
			'<img src="icn/tick-button.png" title="Copy" alt="Copy" />' .
			' &nbsp; <b>Romanization:</b></span> ' . 
			tohtml($romanization) . '</p>';
	}
} else {
	echo '<p>No translation returned. (Too many calls?)' .
		'</p>';
}

echo '<p>&nbsp;</p><p>&nbsp;</p><hr /><p class="smaller">Call to <i>Translate Shell</i> was:<br />';
echo $PATH_TO_TRANS . ' -no-ansi -indent 0 -show-prompt-message n -show-dictionary n -show-languages n -show-alternatives n ' . $from . ':' . $dest . ' "' . $phrase . '"' . '</p>';

pageend();

?>