<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RegistrarBoletoController extends Controller
{
    public function status(){
        return ['status' => 'ok'];
    }

    public function registrar(Request $request){

		$convenio = $request->convenio;
		$numerodacarteira = '17';
		$variacaodacarteira = '19';
		$numerodoboleto = $request->numerodoboleto;
		$datadaemissao = sprintf("%02d", date("d")) . '.' . sprintf("%02d", date("m")) . '.' . date("Y");
		$datadovencimento = $request->datadovencimento; 					// Segundo a especificação, deve ser no formato DD.MM.AAAA
		$valor = $request->valor;											// No formato inglês (sem separador de milhar)
		$tipodedocumentodocliente = 2;										// 1 para CPF e 2 para CNPJ
		$numerodedocumentodocliente = $request->numerodedocumentodocliente;	// CPF ou CNPJ, sem pontos ou traços
		$nomedocliente = $request->nomedocliente;
		$enderecodocliente = $request->enderecodocliente;
		$bairrodocliente = $request->bairrodocliente;
		$municipiodocliente = $request->municipiodocliente;
		$sigladoestadodocliente = $request->sigladoestadodocliente;
		$cepdocliente = $request->cepdocliente;								// Sem pontos ou traços
		$telefonedocliente = $request->telefonedocliente;

		$_clientID = $request->clientID;
        $_secret = $request->secret;

		$parametros = array(
			'numeroConvenio' => $convenio,
			'numeroCarteira' => $numerodacarteira,
			'numeroVariacaoCarteira' => $variacaodacarteira,
			'codigoModalidadeTitulo' => 1,
			'dataEmissaoTitulo' => $datadaemissao,
			'dataVencimentoTitulo' => $datadovencimento,
			'valorOriginalTitulo' => $valor,
			'codigoTipoDesconto' => 0,
			'codigoTipoJuroMora' => 0,
			'codigoTipoMulta' => 0,
			'codigoAceiteTitulo' => 'N',
			'codigoTipoTitulo' => 17,
			'textoDescricaoTipoTitulo' => 'Recibo',
			'indicadorPermissaoRecebimentoParcial' => 'N',
			'textoNumeroTituloBeneficiario' => '1',
			'textoNumeroTituloCliente' => '000' . $convenio . sprintf('%010d', $numerodoboleto),
			'textoMensagemBloquetoOcorrencia' => 'Pagamento disponível até a data de vencimento',
			'codigoTipoInscricaoPagador' => $tipodedocumentodocliente,
			'numeroInscricaoPagador' => $numerodedocumentodocliente,
			'nomePagador' => $nomedocliente,
			'textoEnderecoPagador' => $enderecodocliente,
			'numeroCepPagador' => $cepdocliente,
			'nomeMunicipioPagador' => $municipiodocliente,
			'nomeBairroPagador' => $bairrodocliente,
			'siglaUfPagador' => $sigladoestadodocliente,
			'textoNumeroTelefonePagador' => $telefonedocliente,
			'codigoChaveUsuario' => 1,
			'codigoTipoCanalSolicitacao' => 5,
		);
		
		// Variaveis de produção
        //$_urlToken = 'https://oauth.bb.com.br/oauth/token';
		//$_urlRegistro = 'https://cobranca.bb.com.br:7101/registrarBoleto';
		
		// URL para obtenção da token para testes
		$_urlToken = 'https://oauth.hm.bb.com.br/oauth/token';
		// URL para registro de boleto para teste
		$_urlRegistro = 'https://cobranca.homologa.bb.com.br:7101/registrarBoleto';

        $_timeout = 20;
        
        //$_clientID = 'eyJpZCI6IjgwNDNiNTMtZjQ5Mi00YyIsImNvZGlnb1B1YmxpY2Fkb3IiOjEwOSwiY29kaWdvU29mdHdhcmUiOjEsInNlcXVlbmNpYWxJbnN0YWxhY2FvIjoxfQ';
        //$_secret = 'eyJpZCI6IjBjZDFlMGQtN2UyNC00MGQyLWI0YSIsImNvZGlnb1B1YmxpY2Fkb3IiOjEwOSwiY29kaWdvU29mdHdhcmUiOjEsInNlcXVlbmNpYWxJbnN0YWxhY2FvIjoxLCJzZXF1ZW5jaWFsQ3JlZGVuY2lhbCI6MX0';
    
	    $curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_BINARYTRANSFER => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_POST => true,
			CURLOPT_TIMEOUT => $_timeout,
			CURLOPT_MAXREDIRS => 3
        ));
        curl_setopt_array($curl, array(
			CURLOPT_URL => $_urlToken,
			CURLOPT_POSTFIELDS => 'grant_type=client_credentials&scope=cobranca.registro-boletos',
			CURLOPT_HTTPHEADER => array(
				'Authorization: Basic ' . base64_encode($_clientID . ':' . $_secret),
				'Cache-Control: no-cache'
			)
		));
		$resposta = curl_exec($curl);
        curl_close($curl);
        
		$resultado = json_decode($resposta);
		
		if(isset($resultado->error)){
			return ['error' => $resultado->error, 'error_description' => $resultado->error_description];
		}

		$token = $resultado->access_token;

		// Montar envelope contendo a requisição do serviço
		$requisicao = '<?xml version="1.0" encoding="UTF-8"?><SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xsd="http://www.tibco.com/schemas/bws_registro_cbr/Recursos/XSD/Schema.xsd"><SOAP-ENV:Body><xsd:requisicao>';

		// Coloca cada parâmetro na requisição
		foreach ($parametros as $no => &$valor)
			$requisicao .= "<xsd:$no>" . htmlspecialchars($valor) . "</xsd:$no>";

		// Fecha o nó da requisição, o corpo da mensagem e o envelope
		$requisicao .= '</xsd:requisicao></SOAP-ENV:Body></SOAP-ENV:Envelope>';

		// Preparar requisição
		$curl = curl_init();
		curl_setopt_array($curl, array(
			CURLOPT_BINARYTRANSFER => true,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => 0,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_POST => true,
			CURLOPT_TIMEOUT => $_timeout,
			CURLOPT_MAXREDIRS => 3
        ));
		curl_setopt_array($curl, array(
			CURLOPT_URL => $_urlRegistro,
			CURLOPT_POSTFIELDS => &$requisicao,
			CURLOPT_HTTPHEADER => array(
				'Content-Type: text/xml;charset=UTF-8',
				"Authorization: Bearer $token",
				'SOAPAction: registrarBoleto'
			)
		));
		$resposta = curl_exec($curl);
		curl_close($curl);

		if ($resposta) {
			// Criar documento XML para percorrer os nós da resposta
			$dom = new \DOMDocument('1.0', 'UTF-8');
			// Verificar se o formato recebido é um XML válido.
			// A expressão regular executada por "preg_replace" retira espaços vazios entre tags.
			if (@$dom->loadXML(preg_replace('/(?<=>)\\s+(?=<)/', '', $resposta))) {
				// Realiza o "parse" da resposta a partir do primeiro nó no
				// corpo do documento dentro do envelope
				$resultado = array();
				self::_converterNosXMLEmArray($dom->documentElement->firstChild->firstChild, $resultado);
			} else
				$resultado = false;
		} else {
			$this->_erro = 'Não foi possível conectar-se ao Banco do Brasil';
			return false;
		}

		// Se ocorreu tudo bem, sai
		if (is_array($resultado) && array_key_exists('codigoRetornoPrograma', $resultado) && $resultado['codigoRetornoPrograma'] == 0)
			return $resultado;
        
		return ['Token' => $resultado];
		//return ['XML_DATA' => $resposta];
		
	}
	
	static private function _converterNosXMLEmArray($no, &$resultado) {
		if ($no->firstChild && $no->firstChild->nodeType == XML_ELEMENT_NODE)
			foreach ($no->childNodes as $pos)
			self::_converterNosXMLEmArray($pos, $resultado[$pos->localName]);
		else
			$resultado = html_entity_decode(trim($no->nodeValue));
	}
}
