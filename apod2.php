<?php
/**
 * Created by PhpStorm.
 * User: research
 * Date: 2018-11-14
 * Time: 12:48
 */

/* ue
 * há duas formas de um qq software se associar a outro
 * via código fonte
 * via código binário (exemplo via DLLs)
 *
 */
/*
 * objetivo: fazer um auto downloader da APOD
 * req: 1) que dia é hoje
 */

define("APOD_BASE_URL", "https://apod.nasa.gov/apod/");

function hojeY2M2D2()
{
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
    $pY = ($pY === false) ? intval(date("y")) : $pY;
    $pM = ($pM === false) ? intval(date("m")) : $pM;
    $pD = ($pD === false) ? intval(date("d")) : $pD;

    if ($pY === ($pM === ($pD === false))) {
        $ret = sprintf(
            "%sap%s.html",
            APOD_BASE_URL,
            hojeY2M2D2()
        );
    } else {
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

function todosEnderecosDoMes($pAno, $pMes)
{
    $ret = [];
    for ($dia = 1; $dia <= numDiasNoMes($pAno, $pMes); $dia++) {
        $url = apodAbsoluteUrl($pAno, $pMes, $dia);
        $ret[] = $url;
    }
    return $ret;
}//todosEnderecosDoMes

function numDiasNoMes($pAno, $pMes)
{
    $iAno = intval($pAno);
    $iMes = intval($pMes);
    switch ($iMes) {
        case 1:
        case 3:
        case 5:
        case 7:
        case 8:
        case 10:
        case 12:
            return 31;
        case 4:
        case 6:
        case 9:
        case 11:
            return 30;
        case 2:
            return bissexto($iAno) ? 29 : 28;
    }//switch
}//numDiasNoMes

function bissexto($pAno)
{
    $iAno = intval($pAno);
    $bC1 = $iAno % 4 === 0 && !$iAno % 100 === 0;
    $bC2 = $iAno % 400 === 0;
    return $bC1 || $bC2;
}//bissexto

/*
 * TODO: conferir muita robustez
 */
function garantir2Digitos($pNum)
{
    $iNum = intval($pNum);
    if ($iNum < 10)
        return "0" . $pNum;
    else
        return $pNum;
}//garantir2Digitos

/*
 * com esta ferramenta pretendemos retornar
 * o HTML source code da página HTML APOD (a página do dia)
 * porque, mais tarde, esse HTML deverá ser procurado
 * para nele tentarmos encontrar um elemento img
 */
function apodFetchSrcHtml()
{
    $url = apodAbsoluteUrl();
    $html = getBinaryAtUrl($url);
}//apodFetchSrcHtml

//http://socrates.io/#VEursnf
function getBinaryAtUrl($pUrl)
{
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


$url = apodAbsoluteUrl(
    18,
    11,
    21
);
function nomeParaDownload($pUrl){
   // echo $pUrl;
    $posImagem = strripos($pUrl,"/");
    $nomeImagem = substr($pUrl,$posImagem+1);
    echo $nomeImagem;
    return $nomeImagem;

}
$html = getBinaryAtUrl($url);
$url = urlDiretoParaImagemPorAnaliseDoHtml($html);
//echo $url;
//nomeParaDownload($url);

//echo $html;



function hoeadAllMonthImages($pYear, $pMonth){
    $urlsHtml = todosEnderecosDoMes($pYear,$pMonth);

    foreach ($urlsHtml as $urlHtml){
        $html = getBinaryAtUrl($urlHtml);
        $urlRelativoImg = urlDiretoParaImagemPorAnaliseDoHtml($html);
        $urlAbsImg = APOD_BASE_URL.$urlRelativoImg;
        $nomeParaDl = nomeParaDownload($urlRelativoImg);
        $byteDaImagem = getBinaryAtUrl($urlAbsImg);
        file_put_contents($byteDaImagem,$nomeParaDl);

    }
}

$urls201811 = todosEnderecosDoMes(18, 11);
//var_dump($urls201811);

function downloadDeImagemUrl($pUrl){

    $html = getBinaryAtUrl($pUrl);
    $url = urlDiretoParaImagemPorAnaliseDoHtml($html);
    $binarioImagem = getBinaryAtUrl($url);
    file_put_contents("BIN.JPG",$binarioImagem);
}//downloadDeImagemUrl

function urlDiretoParaImagemPorAnaliseDoHtml($pHtmlParaSerAnalizado){
    $pStrMarcaDeInterece = "<IMG SRC=\"";
    $bCautela = is_string($pHtmlParaSerAnalizado) && strlen($pHtmlParaSerAnalizado) > 0;
    if($bCautela){

        // strpos retorna a primeira posição de match
        // strrpos retorna a última posição de match
        $iPosOndeComecaMarcaDeInteresse = stripos($pHtmlParaSerAnalizado, $pStrMarcaDeInterece);
        $bHaImagemDeInteresse = $iPosOndeComecaMarcaDeInteresse !== false;
        if($bHaImagemDeInteresse){
            $strPorcaoDoHtmlQueMereceNOssaAtencao = substr($pHtmlParaSerAnalizado, $iPosOndeComecaMarcaDeInteresse + strlen($pStrMarcaDeInterece));
            $iPosDaAspaQueencerraImagem = stripos($strPorcaoDoHtmlQueMereceNOssaAtencao, "\"");
            $urlRelativo = substr($strPorcaoDoHtmlQueMereceNOssaAtencao, 0 , $iPosDaAspaQueencerraImagem);
            return $urlRelativo;
        }
    }
    return false;//Não foi possível extrair o url da imagem
}//urlDiretoParaImagemPorAnaliseDoHtml