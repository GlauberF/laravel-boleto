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

namespace EagleSistemas\LaravelBoleto\Cnab\Remessa\Banco;

use EagleSistemas\LaravelBoleto\Cnab\Remessa\AbstractRemessa;
use EagleSistemas\LaravelBoleto\Contracts\Cnab\Remessa as RemessaContract;
use EagleSistemas\LaravelBoleto\Contracts\Boleto\Boleto as BoletoContract;
use EagleSistemas\LaravelBoleto\Util;

class Caixa  extends AbstractRemessa implements RemessaContract
{

    const ESPECIE_DUPLICATA = '01';
    const ESPECIE_NOTA_PROMISSORIA = '02';
    const ESPECIE_DUPLICATA_SERVICO = '03';
    const SPECIE_NOTA_SEGURO = '05';
    const ESPECIE_LETRAS_CAMBIO = '06';
    const ESPECIE_OUTROS = '09';

    const OCORRENCIA_ENTRADA_TITULO = '01';
    const OCORRENCIA_PPEDIDO_BAIXA = '02';
    const OCORRENCIA_CONCESSAO_ABATIMENTO = '03';
    const OCORRENCIA_CANC_ABATIMENTO = '04';
    const OCORRENCIA_ALT_VENC = '05';
    const OCORRENCIA_ALT_USO_EMPRESA = '06';
    const OCORRENCIA_ALT_PRAZO_PROTESTO = '07';
    const OCORRENCIA_ALT_PRAZO_DEVOLUCAO = '08';
    const OCORRENCIA_ALT_OUTROS_DADOS = '09';
    const OCORRENCIA_ALT_OUTROS_DADOS_EMISSAO_BOLETO = '10';
    const OCORRENCIA_ALT_PROTESTO_DEVOLUCAO = '11';
    const OCORRENCIA_ALT_DEVOLUCAO_PROTESTO = '12';

    const INSTRUCAO_PROTESTAR_VENC_XX = '01';
    const INSTRUCAO_DEVOLVER_VENC_XX = '02';

    /**
     * Código do banco
     * @var string
     */
    protected $codigoBanco = self::COD_BANCO_CEF;

    /**
     * Define as carteiras disponíveis para cada banco
     * @var array
     */
    protected $carteiras = ['RG'];

    /**
     * Caracter de fim de linha
     *
     * @var string
     */
    protected $fimLinha = "\r\n";

    /**
     * Caracter de fim de arquivo
     *
     * @var null
     */
    protected $fimArquivo = "\r\n";

    /**
     * Codigo do cliente junto ao banco.
     *
     * @var string
     */
    protected $codigoCliente;

    /**
     * Retorna o codigo do cliente.
     *
     * @return mixed
     */
    public function getCodigoCliente()
    {
        return $this->codigoCliente;
    }

    /**
     * Retorna o numero da carteira, deve ser override em casos de carteira de letras
     *
     * @return string
     */
    public function getCarteiraNumero()
    {
        if ($this->getCarteira() == 'SR'){
            return '02';
        }
        return '01';
    }

    /**
     * Seta o codigo do cliente.
     *
     * @param mixed $codigoCliente
     *
     * @return Caixa
     */
    public function setCodigoCliente($codigoCliente)
    {
        $this->codigoCliente = $codigoCliente;

        return $this;
    }

    protected function header()
    {
        $this->iniciaHeader();

        $this->add(1, 1, '0');
        $this->add(2, 2, '1');
        $this->add(3, 9, 'REMESSA');
        $this->add(10, 11, '01');
        $this->add(12, 26, Util::formatCnab('X', 'COBRANCA', 15));
        $this->add(27, 30, Util::formatCnab('9', $this->getAgencia(), 4));
        $this->add(31, 36, Util::formatCnab('9', $this->getCodigoCliente(), 6));
        $this->add(37, 46, Util::formatCnab('X', '', 10));
        $this->add(47, 76, Util::formatCnab('X', $this->getBeneficiario()->getNome(), 30));
        $this->add(77, 79, $this->getCodigoBanco());
        $this->add(80, 94, Util::formatCnab('X', 'C ECON FEDERAL', 15));
        $this->add(95, 100, date('dmy'));
        $this->add(101, 389, Util::formatCnab('X', '', 289));
        $this->add(390, 394, Util::formatCnab('9', $this->getIdremessa(), 5));
        $this->add(395, 400, Util::formatCnab('9', 1, 6));

        return $this;
    }

    public function addBoleto(BoletoContract $boleto)
    {
        $this->iniciaDetalhe();

        $this->add(1, 1, '1');
        $this->add(2, 3, strlen(Util::onlyNumbers($this->getBeneficiario()->getDocumento())) == 14 ? '02' : '01');
        $this->add(4, 17, Util::formatCnab('9L', $this->getBeneficiario()->getDocumento(), 14));
        $this->add(18, 21, Util::formatCnab('9', $this->getAgencia(), 4));
        $this->add(22, 27, Util::formatCnab('9', $this->getCodigoCliente(), 6));
        $this->add(28, 28, '2'); // ‘1’ = Banco Emite ‘2’ = Cliente Emite
        $this->add(29, 29, '0'); // ‘0’ = Postagem pelo Beneficiário ‘1’ = Pagador via Correio ‘2’ = Beneficiário via Agência CAIXA ‘3’ = Pagador via e-mail
        $this->add(30, 31, '00');
        $this->add(32, 56, Util::formatCnab('X', '', 25)); // numero de controle
        $this->add(57, 73, Util::formatCnab('9', $boleto->getNossoNumero(), 17));
        $this->add(74, 76, Util::formatCnab('X', '', 3));
        $this->add(77, 106, Util::formatCnab('X', '', 30));
        $this->add(107, 108, Util::formatCnab('9', $this->getCarteiraNumero(), 2));
        $this->add(109, 110, '01'); // REGISTRO
        if($boleto->getStatus() == $boleto::STATUS_BAIXA)
        {
            $this->add(109, 110, '02'); // BAIXA
        }
        if($boleto->getStatus() == $boleto::STATUS_ALTERACAO)
        {
            $this->add(109, 110, '05'); // ALTERAR VENCIMENTO
        }
        $this->add(111, 120, Util::formatCnab('X', $boleto->getNumeroDocumento(), 10));
        $this->add(121, 126, $boleto->getDataVencimento()->format('dmy'));
        $this->add(127, 139, Util::formatCnab('9', $boleto->getValor(), 13, 2));
        $this->add(140, 142, $this->getCodigoBanco());
        $this->add(143, 147, '00000');
        $this->add(148, 149, $boleto->getEspecieDocCodigo());
        $this->add(150, 150, $boleto->getAceite());
        $this->add(151, 156, $boleto->getDataDocumento()->format('dmy'));


        $this->add(157, 158, '00');
        $this->add(159, 160, '00');

        if($boleto->getDiasProtesto() !== false)
        {
            $this->add(157, 158, '01');
        }


        $juros = 0;
        if($boleto->getJuros() !== false)
        {
            $juros = Util::percent($boleto->getValor(), $boleto->getJuros())/30;
        }
        $this->add(161, 173, Util::formatCnab('9', $juros, 13, 2));
        $this->add(174, 179, '000000');
        $this->add(180, 192, Util::formatCnab('9', 0, 13, 2));
        $this->add(193, 205, Util::formatCnab('9', 0, 13, 2));
        $this->add(206, 218, Util::formatCnab('9', $boleto->getDescontosAbatimentos(), 13, 2));

        $this->add(219, 220, strlen(Util::onlyNumbers($boleto->getPagador()->getDocumento())) == 14 ? '02' : '01');
        $this->add(221, 234, Util::formatCnab('9L', $boleto->getPagador()->getDocumento(), 14));
        $this->add(235, 274, Util::formatCnab('X', $boleto->getPagador()->getNome(), 40));
        $this->add(275, 314, Util::formatCnab('X', $boleto->getPagador()->getEndereco(), 40));
        $this->add(315, 326, Util::formatCnab('X', '', 12));
        $this->add(327, 334, Util::formatCnab('9L', $boleto->getPagador()->getCep(), 8));
        $this->add(335, 349, Util::formatCnab('X', $boleto->getPagador()->getCidade(), 15));
        $this->add(350, 351, Util::formatCnab('X', $boleto->getPagador()->getUf(), 2));
        $this->add(352, 357, $boleto->getDataVencimento()->copy()->addDays($boleto->getJurosApos(0))->format('dmy'));
        $this->add(358, 367, Util::formatCnab('9', Util::percent($boleto->getValor(), $boleto->getMulta()), 10, 2));

        $this->add(368, 389, Util::formatCnab('X', $boleto->getSacadorAvalista() ? $boleto->getSacadorAvalista()->getNome() : '', 22));
        $this->add(390, 391, '00');
        $this->add(392, 393, Util::formatCnab('9', $boleto->getDiasProtesto('0'), 2));
        $this->add(394, 394, Util::formatCnab('9', $boleto->getMoeda(), 1));
        $this->add(395, 400, Util::formatCnab('9', $this->iRegistros+1, 6));

        return $this;
    }

    protected function trailer()
    {
        $this->iniciaTrailer();

        $this->add(1, 1, '9');
        $this->add(2, 394, '');
        $this->add(395, 400, Util::formatCnab('9', $this->getCount(), 6));

        return $this;
    }

    public function isValid()
    {
        if(empty($this->getCodigoCliente()) || !parent::isValid())
        {
            return false;
        }

        return true;
    }
}