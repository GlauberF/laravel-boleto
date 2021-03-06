<?php
/**
 *   Copyright (c) 2016 Eduardo Gusmão
 *
 *   Permission is hereby granted, free of charge, to any person obtaining a
 *   copy of this software and associated documentation files (the "Software"),
 *   to deal in the Software without restriction, including without limitation
 *   the rights to use, copy, modify, merge, publish, distribute, sublicense,
 *   and/or sell copies of the Software, and to permit persons to whom the
 *   Software is furnished to do so, subject to the following conditions:
 *
 *   The above copyright notice and this permission notice shall be included in all
 *   copies or substantial portions of the Software.
 *
 *   THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 *   INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR
 *   PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 *   COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 *   WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
 *   IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace EagleSistemas\LaravelBoleto\Boleto\Render;

use EagleSistemas\LaravelBoleto\Contracts\Boleto\Boleto as BoletoInterface;
use EagleSistemas\LaravelBoleto\Contracts\Boleto\Render\Pdf as PdfContract;
use EagleSistemas\LaravelBoleto\Util;

class Pdf extends AbstractPdf implements PdfContract
{

    const OUTPUT_STANDARD   = 'I';
    const OUTPUT_DOWNLOAD   = 'D';
    const OUTPUT_SAVE       = 'F';
    const OUTPUT_STRING     = 'S';

    private $PadraoFont = 'Arial';
    /**
     * @var BoletoInterface[]
     */
    private $boleto = array();

    private $desc  =   3;  // tamanho célula descrição
    private $cell  =   4;  // tamanho célula dado
    private $fdes  =   6;  // tamanho fonte descrição
    private $fcel  =   8;  // tamanho fonte célula
    private $small = 0.2;  // tamanho barra fina
    private $large = 0.6;  // tamanho barra larga
    private $totalBoletos = 0;

    function __construct() {
        parent::__construct('P','mm','A4');
        $this->SetLeftMargin(20);
        $this->SetTopMargin(5);
        $this->SetRightMargin(20);
        $this->SetLineWidth($this->small);
    }

    /**
     * @param $i
     *
     * @return $this
     */
    protected function instrucoes($i){

        $this->SetFont($this->PadraoFont,'', 8);
        if( $this->totalBoletos > 1 ){
            $this->Cell(30, 10, date('d/m/Y H:i:s'));
            $this->Cell(0, 10, "Boleto " . ($i+1) . " de " . $this->totalBoletos, 0, 1, 'R');
        }

        $this->SetFont($this->PadraoFont,'B', 8);
        $this->Cell(0, 5, $this->_('Instruções de Impressão'), 0, 1, 'C');
        $this->Ln(3);
        $this->SetFont($this->PadraoFont,'',6);
        $this->Cell(0, $this->desc, $this->_('- Imprima em impressora jato de tinta (ink jet) ou laser em qualidade normal ou alta (Não use modo econômico).'), 0, 1, 'L');
        $this->Cell(0, $this->desc, $this->_('- Utilize folha A4 (210 x 297 mm) ou Carta (216 x 279 mm) e margens mínimas à esquerda e à direita do formulário.'), 0, 1, 'L');
        $this->Cell(0, $this->desc, $this->_('- Corte na linha indicada. Não rasure, risque, fure ou dobre a região onde se encontra o código de barras.'), 0, 1, 'L');
        $this->Cell(0, $this->desc, $this->_('- Caso não apareça o código de barras no final, clique em F5 para atualizar esta tela.'), 0, 1, 'L');
        $this->Cell(0, $this->desc, $this->_('- Caso tenha problemas ao imprimir, copie a seqüencia numérica abaixo e pague no caixa eletrônico ou no internet banking:'), 0, 1, 'L');
        $this->Ln(4);

        $this->SetFont($this->PadraoFont, '', $this->fcel);
        $this->Cell(25, $this->cell, $this->_('Linha Digitável: '), 0, 0);
        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell( 0, $this->cell, $this->_( $this->boleto[$i]->getLinhaDigitavel() ), 0, 1);
        $this->SetFont($this->PadraoFont, '', $this->fcel);
        $this->Cell(25, $this->cell, $this->_('Valor: '), 0, 0);
        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell( 0, $this->cell, $this->_( Util::nReal($this->boleto[$i]->getValor()) ));
        $this->SetFont($this->PadraoFont, '', $this->fcel);

        $this->traco('Recibo do Pagador', 4);

        return $this;
    }

    /**
     * @param $i
     *
     * @return $this
     */
    protected function logoEmpresa($i){

        $this->Ln(10);
        $this->SetFont($this->PadraoFont, '', $this->fdes);

        $logo = $url = preg_replace('/\&.*/', '', $this->boleto[$i]->getLogo());
        $ext = pathinfo($logo, PATHINFO_EXTENSION);

        $this->Image( $this->boleto[$i]->getLogo(), 20, ($this->GetY()), 0 , 12, $ext);
        $this->Cell(56);
        $this->Cell(0, $this->desc, $this->_( $this->boleto[$i]->getBeneficiario()->getNome() ),0,1);
        $this->Cell(56);
        $this->Cell(0, $this->desc, $this->_( $this->boleto[$i]->getBeneficiario()->getDocumento(), '##.###.###/####-##'),0,1);
        $this->Cell(56);
        $this->Cell(0, $this->desc, $this->_( $this->boleto[$i]->getBeneficiario()->getEndereco() ),0,1);
        $this->Cell(56);
        $this->Cell(0, $this->desc, $this->_( $this->boleto[$i]->getBeneficiario()->getCepCidadeUf() ),0,1);
        $this->Ln(10);

        return $this;
    }

    /**
     * @param $i
     *
     * @return $this
     */
    protected function Topo($i) {

        $this->Image( $this->boleto[$i]->getLogoBanco(), 20, ($this->GetY()-2), 28);
        $this->Cell(29, 6, '', 'B');
        $this->SetFont('','B',13);
        $this->Cell(15, 6, $this->boleto[$i]->getCodigoBancoComDv(), 'LBR', 0, 'C');
        $this->SetFont('','B',10);
        $this->Cell(0, 6, $this->boleto[$i]->getLinhaDigitavel() ,'B',1,'R');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(75, $this->desc, $this->_('Beneficiário'), 'TLR');
        $this->Cell(35, $this->desc, $this->_('Agencia/Codigo do beneficiário'), 'TR');
        $this->Cell(10, $this->desc, $this->_('Espécie'), 'TR');
        $this->Cell(15, $this->desc, $this->_('Quantidade'), 'TR');
        $this->Cell(35, $this->desc, $this->_('Nosso Numero'), 'TR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);

        $this->textFitCell(75, $this->cell, $this->_( $this->boleto[$i]->getBeneficiario()->getNome() ), 'LR', 0, 'L');

        $this->Cell(35, $this->cell, $this->_( $this->boleto[$i]->getAgenciaCodigoBeneficiario() ), 'R');
        $this->Cell(10, $this->cell, $this->_( 'R$' ), 'R');
        $this->Cell(15, $this->cell, $this->_( '1' ), 'R');
        $this->Cell(35, $this->cell, $this->_( $this->boleto[$i]->getNossoNumeroBoleto() ), 'R' , 1, 'R');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(50, $this->desc, $this->_('Número do Documento'), 'TLR');
        $this->Cell(40, $this->desc, $this->_('CPF/CNPJ'), 'TR');
        $this->Cell(30, $this->desc, $this->_('Vencimento'), 'TR');
        $this->Cell(50, $this->desc, $this->_('Valor do Documento'), 'TR' , 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(50, $this->cell, $this->_( $this->boleto[$i]->getNumeroDocumento() ), 'LR');
        $this->Cell(40, $this->cell, $this->_( $this->boleto[$i]->getBeneficiario()->getDocumento() , '##.###.###/####-##' ), 'R');
        $this->Cell(30, $this->cell, $this->_( $this->boleto[$i]->getDataVencimento()->format('d/m/Y') ), 'R');
        $this->Cell(50, $this->cell, $this->_( Util::nReal($this->boleto[$i]->getValor())), 'R', 1, 'R');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(30, $this->desc, $this->_('(-) Descontos/Abatimentos'), 'TLR');
        $this->Cell(30, $this->desc, $this->_('(-) Outras Deduções'), 'TR');
        $this->Cell(30, $this->desc, $this->_('(+) Mora Multa'), 'TR');
        $this->Cell(30, $this->desc, $this->_('(+) Acréscimos'), 'TR');
        $this->Cell(50, $this->desc, $this->_('(=) Valor Cobrado'), 'TR' , 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(30, $this->cell, $this->_(''), 'LR');
        $this->Cell(30, $this->cell, $this->_(''), 'R');
        $this->Cell(30, $this->cell, $this->_(''), 'R');
        $this->Cell(30, $this->cell, $this->_(''), 'R');
        $this->Cell(50, $this->cell, $this->_(''), 'R' , 1, 'R');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(0, $this->desc, $this->_('Pagador'), 'TLR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(0, $this->cell, $this->_( $this->boleto[$i]->getPagador()->getNomeDocumento() ), 'BLR', 1);

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(100, $this->desc, $this->_('Demonstrativo'), 0, 0,'L');
        $this->Cell(0, $this->desc, $this->_('Autenticação mecânica'), 0, 1,'R');
        $this->Ln(2);

        $pulaLinha = 16;

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        if(count($this->boleto[$i]->getDescricaoDemonstrativo()) > 0)
        {
            foreach ($this->boleto[$i]->getDescricaoDemonstrativo() as $d) {
                $pulaLinha -= 2;
                $this->Cell (0, $this->cell, $this->_( preg_replace('/(%)/', '%$1', $d)), 0, 1);
            }
        }

        $this->traco('Corte na linha pontilhada',$pulaLinha,5);

        return $this;

    }

    /**
     * @param $i
     *
     * @return $this
     */
    protected function Bottom($i){

        $this->Image($this->boleto[$i]->getLogoBanco(), 20, ($this->GetY()-2), 28);
        $this->Cell(29, 6, '', 'B');
        $this->SetFont($this->PadraoFont, 'B', 13);
        $this->Cell(15, 6, $this->boleto[$i]->getCodigoBancoComDv(), 'LBR', 0, 'C');
        $this->SetFont($this->PadraoFont, 'B', 10);
        $this->Cell(0, 6, $this->boleto[$i]->getLinhaDigitavel() ,'B',1,'R');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(120, $this->desc, $this->_('Local de pagamento'), 'TLR');
        $this->Cell(50, $this->desc, $this->_('Vencimento'), 'TR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(120, $this->cell, $this->_( $this->boleto[$i]->getLocalPagamento() ), 'LR');
        $this->Cell(50, $this->cell, $this->_( $this->boleto[$i]->getDataVencimento()->format('d/m/Y') ), 'R', 1, 'R');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(120, $this->desc, $this->_('Beneficiário'), 'TLR');
        $this->Cell(50, $this->desc, $this->_('Agência/Código beneficiário'), 'TR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(120, $this->cell, $this->_( $this->boleto[$i]->getBeneficiario()->getNomeDocumento() ), 'LR');
        $this->Cell(50, $this->cell, $this->_( $this->boleto[$i]->getAgenciaCodigoBeneficiario() ), 'R', 1, 'R');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(30, $this->desc, $this->_('Data do documento'), 'TLR');
        $this->Cell(40, $this->desc, $this->_('Número do documento'), 'TR');
        $this->Cell(15, $this->desc, $this->_('Espécie Doc.'), 'TR');
        $this->Cell(10, $this->desc, $this->_('Aceite'), 'TR');
        $this->Cell(25, $this->desc, $this->_('Data processamento'), 'TR');
        $this->Cell(50, $this->desc, $this->_('Nosso número'), 'TR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(30, $this->cell, $this->_( $this->boleto[$i]->getDataDocumento()->format('d/m/Y') ), 'LR');
        $this->Cell(40, $this->cell, $this->_( $this->boleto[$i]->getNumero() ), 'R');
        $this->Cell(15, $this->cell, $this->_( $this->boleto[$i]->getEspecieDoc() ), 'R');
        $this->Cell(10, $this->cell, $this->_( $this->boleto[$i]->getAceite() ), 'R');
        $this->Cell(25, $this->cell, $this->_( $this->boleto[$i]->getDataProcessamento()->format('d/m/Y') ), 'R');
        $this->Cell(50, $this->cell, $this->_( $this->boleto[$i]->getNossoNumeroBoleto() ), 'R', 1, 'R');

        $this->SetFont($this->PadraoFont, '', $this->fdes);

        if(isset($this->boleto[$i]->variaveis_adicionais['esconde_uso_banco']) and $this->boleto[$i]->variaveis_adicionais['esconde_uso_banco'])
        {
            $this->Cell(55, $this->desc, $this->_('Carteira'), 'TLR');
        }
        else
        {
            $cip = isset($this->boleto[$i]->variaveis_adicionais['mostra_cip']) and $this->boleto[$i]->variaveis_adicionais['mostra_cip'];

            $this->Cell(($cip ? 25 : 30), $this->desc, $this->_('Uso do Banco'), 'TLR');
            if($cip)
            {
                $this->Cell(5, $this->desc, $this->_('CIP'), 'TLR');
            }
            $this->Cell(25, $this->desc, $this->_('Carteira') , 'TR');
        }

        $this->Cell(12, $this->desc, $this->_('Espécie'), 'TR');
        $this->Cell(28, $this->desc, $this->_('Quantidade'), 'TR');
        $this->Cell(25, $this->desc, $this->_('Valor Documento'), 'TR');
        $this->Cell(50, $this->desc, $this->_('Valor Documento'), 'TR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);

        if(isset($this->boleto[$i]->variaveis_adicionais['esconde_uso_banco']) and $this->boleto[$i]->variaveis_adicionais['esconde_uso_banco'])
        {
            $this->TextFitCell(55, $this->cell, $this->_( $this->boleto[$i]->getCarteiraNome()), 'LR', 0, 'L');
        }
        else
        {
            $cip = isset($this->boleto[$i]->variaveis_adicionais['mostra_cip']) and $this->boleto[$i]->variaveis_adicionais['mostra_cip'];
            $this->Cell(($cip ? 25 : 30), $this->cell, $this->_( '' ), 'LR');
            if($cip)
            {
                $this->Cell(5, $this->desc, $this->_($this->boleto[$i]->getCip()), 'TLR');
            }
            $this->Cell(25, $this->cell, $this->_( strtoupper( $this->boleto[$i]->getCarteiraNome() ) ), 'R');
        }

        $this->Cell(12, $this->cell, $this->_( 'R$' ), 'R');
        $this->Cell(28, $this->cell, $this->_( '1' ), 'R');
        $this->Cell(25, $this->cell, $this->_( Util::nReal($this->boleto[$i]->getValor()) ), 'R');
        $this->Cell(50, $this->cell, $this->_( Util::nReal($this->boleto[$i]->getValor()) ), 'R', 1, 'R');

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(120, $this->desc, $this->_('Instruções (Texto de responsabilidade do beneficiário)'), 'TLR');
        $this->Cell(50, $this->desc, $this->_('(-) Desconto / Abatimentos)'), 'TR', 1);

        $xInstrucoes = $this->GetX();
        $yInstrucoes = $this->GetY();

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(120, $this->cell, $this->_(''), 'LR');
        $this->Cell(50, $this->cell, $this->_(''), 'R', 1);

        $this->Cell(120, $this->desc, $this->_(''), 'LR');
        $this->Cell(50, $this->desc, $this->_('(-) Outras deduções'), 'TR', 1);

        $this->Cell(120, $this->cell, $this->_(''), 'LR');
        $this->Cell(50, $this->cell, $this->_(''), 'R', 1);

        $this->Cell(120, $this->desc, $this->_(''), 'LR');
        $this->Cell(50, $this->desc, $this->_('(+) Mora / Multa'), 'TR', 1);

        $this->Cell(120, $this->cell, $this->_(''), 'LR');
        $this->Cell(50, $this->cell, $this->_(''), 'R', 1);

        $this->Cell(120, $this->desc, $this->_(''), 'LR');
        $this->Cell(50, $this->desc, $this->_('(+) Outros acréscimos'), 'TR', 1);

        $this->Cell(120, $this->cell, $this->_(''), 'LR');
        $this->Cell(50, $this->cell, $this->_(''), 'R', 1);

        $this->Cell(120, $this->desc, $this->_(''), 'LR');
        $this->Cell(50, $this->desc, $this->_('(=) Valor cobrado'), 'TR', 1);

        $this->Cell(120, $this->cell, $this->_(''), 'BLR');
        $this->Cell(50, $this->cell, $this->_(''), 'BR', 1);

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(0, $this->desc, $this->_('Pagador'), 'LR', 1);

        $this->SetFont($this->PadraoFont, 'B', $this->fcel);
        $this->Cell(0, $this->cell, $this->_( $this->boleto[$i]->getPagador()->getNomeDocumento() ), 'LR', 1);
        $this->Cell(0, $this->cell, $this->_( $this->boleto[$i]->getPagador()->getEndereco() ), 'LR', 1);
        $this->Cell(0, $this->cell, $this->_( $this->boleto[$i]->getPagador()->getCepCidadeUf() ), 'LR', 1);

        $this->SetFont($this->PadraoFont, '', $this->fdes);
        $this->Cell(120, $this->cell, $this->_(''), 'BLR');
        $this->Cell(12, $this->cell, $this->_( 'Cód. Baixa' ), 'B');
        $this->SetFont($this->PadraoFont,'B',  $this->fcel);
        $this->Cell(38  , $this->cell, $this->_( '' ), 'BR', 1);

        $this->SetFont($this->PadraoFont,'',  $this->fdes);
        $this->Cell(20, $this->desc, $this->_( 'Sacador/Avalista' ), 0);
        $this->Cell(98, $this->desc, $this->_( '' ), 0);
        $this->Cell(52, $this->desc, $this->_( 'Autenticação mecânica - Ficha de Compensação' ),0,1);

        $xOriginal = $this->GetX();
        $yOriginal = $this->GetY();

        if(count($this->boleto[$i]->getInstrucoes()) > 0)
        {
            $this->SetXY($xInstrucoes, $yInstrucoes);
            $this->Ln(1);
            $this->SetFont($this->PadraoFont,'B',  $this->fcel);
            foreach($this->boleto[$i]->getInstrucoes() as $in )
                $this->Cell (0, $this->cell, $this->_(preg_replace('/(%)/', '%$1', $in)), 0, 1);

            $this->SetXY($xOriginal, $yOriginal);
        }
        return $this;
    }

    /**
     * @param      $texto
     * @param null $ln
     * @param null $ln2
     */
    protected function traco($texto,$ln= null,$ln2 = null) {
        if($ln) $this->Ln ($ln);
        $this->SetFont($this->PadraoFont, '', $this->fdes);
        if($texto){
            $this->Cell(0, 2, $this->_($texto), 0, 1, 'R');
        }
        $this->Cell(0, 2, str_pad('-', '261', ' -', STR_PAD_RIGHT), 0, 1);
        if($ln2) $this->Ln ($ln2);
    }

    /**
     *
     */
    protected function risco(){
        $this->SetLineWidth($this->large);
        $this->Line(20.3, $this->GetY(), 189.7, $this->GetY());
        $this->SetLineWidth($this->small);
    }

    /**
     * @param $i
     */
    protected function codigoBarras($i){
        $this->Ln(2);
        $this->Cell(0, 15, '', 0, 1, 'L');
        $this->i25($this->GetX(),  $this->GetY()-15, $this->boleto[$i]->getCodigoBarras(), 0.8, 15);
    }

    /**
     * Addiciona o boleto
     *
     * @param BoletoInterface $boleto
     */
    public function addBoleto(BoletoInterface $boleto){
        $this->totalBoletos += 1;
        $this->boleto[] = $boleto;
    }

    /**
     * função para gerar o boleto
     *
     * @param string $dest  tipo de destino const BOLETOPDF_DEST_STANDARD | BOLETOPDF_DEST_DOWNLOAD | BOLETOPDF_DEST_SAVE | BOLETOPDF_DEST_STRING
     * @param null   $save_path
     * @param bool   $print se vai solicitar impressão
     *
     * @return string
     * @throws \Exception
     */
    public function gerarBoleto($dest = self::OUTPUT_STANDARD, $save_path = null, $print = false){

        if($this->totalBoletos == 0)
        {
            throw new \Exception ('Nenhum Boleto adicionado');
        }

        for( $i = 0 ; $i < $this->totalBoletos ; $i ++ ){
            $this->SetDrawColor('0','0','0');
            $this->AddPage();
            $this->instrucoes($i)->logoEmpresa($i)->Topo($i)->Bottom($i)->codigoBarras($i);
        }
        if($dest == self::OUTPUT_SAVE)
        {
            $this->Output($save_path, $dest, $print);
            return $save_path;
        }
        return $this->Output(rand(1,9999).'.pdf', $dest, $print);
    }
}