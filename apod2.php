<?php
/**
 * Created by.
 * User: research
 * Date: 2018-11-14
 * Time: 12:48
 */

/*
 * há duas formas de um qq software se associar a outro
 * via código fonte
 * via código binário (exemplo via DLLs)
 *
 */
/*
 * objetivo: fazer um auto downloader da APOD
 * req: 1) que dia é hoje
 */

define ("APOD_BASE_URL", "https://apod.nasa.gov/apod/");

function hojeY2M2D2(){
    return date("ymd"); //"181114" quando for 2018-11-14
}//hojeY2M2D2

//https://apod.nasa.gov/apod/ap
//$ano2digitos$mes2digitos$dia2digitos.html
function apodAbsoluteUrl(
    $pY = false,
    $pM = false,
    $pD = false
)
{
    /*
     * se tiver sido feita uma chamada SEM argumentos
     * como a chamada feita até 20181114
     * vai tomar-se a data "corrente" para produção do URL
     * sendo recebido argumentos são (cegamente) respeitados
     * TODO: como verificar a validade dos args
     * e não aceitá-los cegamente?!
     */
    $pY = ($pY===false) ? intval(date("y")) : $pY;
    $pM = ($pM===false) ? intval(date("m")) : $pM;
    $pD = ($pD===false) ? intval(date("d")) : $pD;

    if ($pY===($pM===($pD===false))){
        $ret = sprintf(
            "%sap%s.html",
            APOD_BASE_URL,
            hojeY2M2D2()
        );
    }
    else{
        //TODO: garantir o ano com 4d, o mês com 2d, e o dia com 2d
        $ret = sprintf(
            "%sap%s%s%s.html",
            APOD_BASE_URL,
            garantir2Digitos($pY),
            garantir2Digitos($pM),
            garantir2Digitos($pD)
        );
    }

    return $ret;
}//apodAbsoluteUrl

function todosEnderecosDoMes($pAno, $pMes){
    $ret = [];
    for ($dia=1; $dia<=numDiasNoMes($pAno, $pMes); $dia++){
        $url = apodAbsoluteUrl($pAno, $pMes, $dia);
        $ret[] = $url;
    }
    return $ret;
}//todosEnderecosDoMes

function numDiasNoMes ($pAno, $pMes){
    $iAno = intval($pAno); $iMes = intval($pMes);
    switch($iMes){
        case 1:case 3:case 5:case 7:case 8:case 10:case 12:
        return 31;
        case 4:case 6:case 9:case 11: return 30;
        case 2: return bissexto($iAno) ? 29 : 28;
    }//switch
}//numDiasNoMes

function bissexto($pAno){
    $iAno = intval($pAno);
    $bC1 = $iAno%4===0 && !$iAno%100===0;
    $bC2 = $iAno%400===0;
    return $bC1 || $bC2;
}//bissexto

/*
 * TODO: conferir muita robustez
 */
function garantir2Digitos ($pNum){
    $iNum = intval($pNum);
    if ($iNum<10)
        return "0".$pNum;
    else
        return $pNum;
}//garantir2Digitos

/*
 * com esta ferramenta pretendemos retornar
 * o HTML source code da página HTML APOD (a página do dia)
 * porque, mais tarde, esse HTML deverá ser procurado
 * para nele tentarmos encontrar um elemento img
 */
function apodFetchSrcHtml(){
    $url = apodAbsoluteUrl();
    $html = getBinaryAtUrl($url);
}//apodFetchSrcHtml

//http://socrates.io/#VEursnf
function getBinaryAtUrl($pUrl){
    $curlHandler = curl_init();
    $curlHandler = curl_init($pUrl);
    $bResult = curl_setopt
    ($curlHandler, CURLOPT_URL, $pUrl);
    curl_setopt
    ($curlHandler, CURLOPT_BINARYTRANSFER, true);
    /*
     * se esta opção for true
     * curl_exec retorna conteúdo
     * em vez de apenas true ou false, consoante tenha sido
     * bem sucedido, ou não
     */
    curl_setopt
    ($curlHandler, CURLOPT_RETURNTRANSFER, true);
    curl_setopt
    ($curlHandler, CURLOPT_SSL_VERIFYPEER, false);

    //pode demorar a executar => é aqui q ocorre o consumo
    $bin = curl_exec($curlHandler);
    return $bin;
}//getBinaryAtUrl

//http://socrates.io/#1H3PWww
function urlDiretoParaImagemPorAnaliseDoHtml(
    $pHtmlParaSerAnalisado,
    $pStrMarcaDeInteresse = "<IMG SRC=\""
){
    $bCautela =
        is_string($pHtmlParaSerAnalisado)
        &&
        strlen($pHtmlParaSerAnalisado)>0;

    if ($bCautela){
        $iPosOndeComecaMarcaDeInteresse =
            //strpos, stripos retornam a primeira pos de match
            //strrpos, strripos retornam a última pos de match
            //ex strRightmostpos("ABBCCC", "B") -> 2
            //ex strpos ("ABBCCC", "B") -> 1
            stripos(
                $pHtmlParaSerAnalisado,
                $pStrMarcaDeInteresse
            );

        $bHaImagemDeInteresse =
            $iPosOndeComecaMarcaDeInteresse!==false;

        if ($bHaImagemDeInteresse){
            $strPorcaoDoHtmlQueMereceNossaAtencao =
                substr(
                    $pHtmlParaSerAnalisado,
                    $iPosOndeComecaMarcaDeInteresse
                    + strlen($pStrMarcaDeInteresse)
                );
            $iPosDaAspaQueEncerraImagem =
                stripos(
                    $strPorcaoDoHtmlQueMereceNossaAtencao,
                    "\"" //ou '"'
                );
            $urlRelativo = substr(
                $strPorcaoDoHtmlQueMereceNossaAtencao,
                0,
                $iPosDaAspaQueEncerraImagem
            );

            return $urlRelativo;
        }
    }
    return false; //não foi possível extrair o url relativo da imagem
}//urlDiretoParaImagemPorAnaliseDoHtml

//data hoarding

function donwloadDeImagemNoUrl(
    $pUrl
){
    /*
     * na var $html ficará o source code da página APOD
     * mencionada em $pUrl
     * por exemplo, se $pUrl for
     * https://apod.nasa.gov/apod/ap181102.html
     * fica o HTML dessa página
     *
     * porque é que queremos ter acesso ao HTML
     * para nele procurarmos a referência à imagem
     * por análise de source codes em APOD já percebemos
     * que, a haver uma imagem, será o ÚNICO elemento
     * <IMG
     */
    $html = getBinaryAtUrl($pUrl);
    $url =
        urlDiretoParaImagemPorAnaliseDoHtml($html);
    $binarioZerosUnsDaImagem = getBinaryAtUrl($url);
    file_put_contents(
        "BIN.JPG", //conseguir nomes corretos para files
        $binarioZerosUnsDaImagem
    );
}//donwloadDeImagemNoUrl

function hoardAllMonthImages(
    $pYear,
    $pMonth
){
    $urlsHtml = todosEnderecosDoMes($pYear, $pMonth);
    foreach($urlsHtml as $urlHtml){
        $html = getBinaryAtUrl($urlHtml);
        $urlRelativoImg =
            urlDiretoParaImagemPorAnaliseDoHtml($html);

        if ($urlRelativoImg!==false){
            $urlAbsImg = APOD_BASE_URL.$urlRelativoImg;
            echo "About to dl: $urlAbsImg".PHP_EOL;

            $nomeParaDl = nomeParaDownload($urlRelativoImg);
            $bytesDaImagem = getBinaryAtUrl($urlAbsImg);

            $bytesEscritos =
                file_put_contents(
                    $nomeParaDl,
                    $bytesDaImagem
                );
            echo
                "$bytesEscritos byte(s) escritos para $nomeParaDl.".
                PHP_EOL;
        }

    }//foreach
}//hoardAllMonthImages

/*
 * escreva uma solução
 * "nomeParaDownload", que recebido um URL relativo
 * como o exemplificado de seguida
 * image/1811/JupiterSwirls_JunoBrealey_960.jpg
 * retorna um nome adequado para download do conteúdo
 * referido, neste caso
 * JupiterSwirls_JunoBrealey_960.jpg
 */
function nomeParaDownload (
    $pUrlRelativo
){
    $ret = "";

    $iPosDePartida = strrpos($pUrlRelativo, "/")+1;
    $ret= substr(
        $pUrlRelativo,
        $iPosDePartida
    );

    return $ret;
}//nomeParaDownload

/*
echo nomeParaDownload
("image/1811/JupiterSwirls_JunoBrealey_960.jpg");
*/

hoardAllMonthImages(18, 10);