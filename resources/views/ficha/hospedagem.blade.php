<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ficha de Hospedagem - ACADEPOL</title>
    <style>
        @page {
            size: A4;
            margin: 1cm;
        }
        body {
            font-family: Arial, sans-serif;
            font-size: 10pt;
            line-height: 1.2;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 21cm;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            margin-bottom: 10px;
            font-weight: bold;
            font-size: 14pt;
            position: relative;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
        }
        .logo {
            position: absolute;
            left: 0;
            top: 5px;
            font-size: 9pt;
            font-weight: bold;
        }
        .ap-number {
            border: 1px solid #000;
            width: 100px;
            height: 50px;
            position: absolute;
            right: 0;
            top: 0;
            text-align: center;
            padding-top: 10px;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }
        td, th {
            border: 1px solid #000;
            padding: 4px;
        }
        th {
            background-color: #ceb87c;
            font-weight: bold;
        }
        .checkbox-grid {
            display: grid;
            grid-template-columns: 20px auto;
            align-items: center;
            margin-bottom: 3px;
        }
        .checkbox {
            width: 15px;
            height: 15px;
            border: 1px solid #000;
            display: inline-block;
        }
        .terms {
            background-color: #ceb87c;
            font-weight: bold;
            padding: 5px;
            margin-top: 5px;
            margin-bottom: 5px;
        }
        .terms-content {
            border: 1px solid #000;
            padding: 10px;
            margin-bottom: 10px;
            font-size: 9pt;
            line-height: 1.1;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 30px;
            width: 100%;
            text-align: center;
            font-size: 8pt;
        }
        .page-break {
            page-break-after: always;
        }
        .small-text {
            font-size: 8pt;
        }
        .term-item {
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">ACADEPOL Logo</div>
            FICHA DE HOSPEDAGEM
            <div class="ap-number">
                AP. Nº<br>
                <div style="margin-top: 15px; border-bottom: 1px solid black; width: 70%; margin-left: auto; margin-right: auto;"></div>
            </div>
        </div>

        <table>
            <tr>
                <th width="65%">NOME DO RESIDENTE:</th>
                <th width="10%">RG:</th>
                <th width="10%">ORG. EXPEDIDOR:</th>
                <th width="15%">CPF:</th>
            </tr>
            <tr>
                <td>Gabriel Matias da Nóbrega</td>
                <td>4230326</td>
                <td>SSDS</td>
                <td>12162723441</td>
            </tr>
        </table>

        <table>
            <tr>
                <th width="15%">DATA NASC:</th>
                <th width="15%">MATRÍCULA:</th>
                <th width="10%">SEXO:</th>
                <th width="30%">CARGO, FUNÇÃO OU PROFISSÃO:</th>
                <th width="15%">TELEFONE:</th>
                <th width="15%">CONTATO:</th>
            </tr>
            <tr>
                <td>15/09/1996</td>
                <td>1932721</td>
                <td>M</td>
                <td>Escrivão de Polícia</td>
                <td>(83) 98916-0346</td>
                <td></td>
            </tr>
        </table>

        <table>
            <tr>
                <th width="80%">ENDEREÇO:</th>
                <th width="20%">Nº:</th>
            </tr>
            <tr>
                <td>Antônia Fernandes de Oliveira, 13</td>
                <td>13</td>
            </tr>
        </table>

        <table>
            <tr>
                <th width="33%">BAIRRO:</th>
                <th width="33%">CIDADE:</th>
                <th width="10%">UF:</th>
                <th width="24%">E-MAIL:</th>
            </tr>
            <tr>
                <td>Jardim Camboinha</td>
                <td>Cabedelo</td>
                <td>PB</td>
                <td>matiasnobrega7@gmail.com</td>
            </tr>
        </table>

        <table>
            <tr>
                <th width="70%">ESPECIFICAR:</th>
                <th width="30%">CONDIÇÃO DO ALOJADO:</th>
            </tr>
            <tr>
                <td>Curso de Tiro</td>
                <td>Visitante</td>
            </tr>
        </table>

        <table>
            <tr>
                <th>MOTIVO DA RESERVA:</th>
            </tr>
            <tr>
                <td style="height: 30px;">Curso de Tiro</td>
            </tr>
        </table>

        <table>
            <tr>
                <th width="80%">ÓRGÃO/INSTITUIÇÃO DE ORIGEM:</th>
                <th width="20%">UF:</th>
            </tr>
            <tr>
                <td>Polícia Civil</td>
                <td>PB</td>
            </tr>
        </table>

        <table>
            <tr>
                <th width="80%">TERMO DE RESPONSABILIDADE</th>
                <th width="20%">RELAÇÃO DOS BENS:</th>
            </tr>
            <tr>
                <td style="vertical-align: top; height: 100px;">
                    Recebi o apartamento identificado pelo número acima para permanência no período de <strong>17/04/2025</strong> a partir das ________ horas até <strong>23/04/2025</strong> às ________ horas, com os bens ao lado relacionados, responsabilizando-me em conservá-los e mantê-los conforme recebido, bem como procederei desocupação do recinto na data e horário informadas.
                </td>
                <td style="vertical-align: top;">
                    <div class="checkbox-grid">
                        <div class="checkbox"></div>
                        <div>BELICHE:</div>
                    </div>
                    <div class="checkbox-grid">
                        <div class="checkbox"></div>
                        <div>CADEIRA BASE FIXA:</div>
                    </div>
                    <div class="checkbox-grid">
                        <div class="checkbox"></div>
                        <div>COLCHÃO:</div>
                    </div>
                    <div class="checkbox-grid">
                        <div class="checkbox"></div>
                        <div>CADEIRA RODÍZIO:</div>
                    </div>
                    <div class="checkbox-grid">
                        <div class="checkbox"></div>
                        <div>GAVETEIRO:</div>
                    </div>
                    <div class="checkbox-grid">
                        <div class="checkbox"></div>
                        <div>OUTROS:</div>
                    </div>
                    <div>ESPECIFICAR:</div>
                    <div class="checkbox-grid">
                        <div class="checkbox"></div>
                        <div>GUARDA-ROUPA:</div>
                    </div>
                    <div class="checkbox-grid">
                        <div class="checkbox"></div>
                        <div>ESCRIVANINHA:</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="terms">TERMOS DE UTILIZAÇÃO DO ALOJAMENTO</div>
    
        <div class="terms-content">
            <div class="term-item">1. Deverá ser observado o limite das 23h00min, inclusive aos finais de semana, para ingresso na Acadepol, sendo proibida a entrada após esse horário, exceto com expressa autorização do Diretor da Academia de Ensino e após contato prévio;</div>
            <div class="term-item">2. É VEDADO:</div>
            <div class="term-item small-text">- Uso de bebidas alcoólicas e/ou qualquer substância alucinógena;</div>
            <div class="term-item small-text">- Receber visitas no interior dos alojamentos;</div>
            <div class="term-item small-text">- Emitir ruídos que comprometam a ordem ou o sossego;</div>
            <div class="term-item small-text">- Mudar ou alterar a disposição dos quartos;</div>
            <div class="term-item small-text">- Expor peças nas janelas, nas áreas internas de circulação comum ou secá-las na parte traseira dos refrigeradores;</div>
            <div class="term-item small-text">- Deixar lixos pelos espaços comuns e privados;</div>
            <div class="term-item small-text">- Utilização de fogareiros e resistências (tipo ebulidor), para esquentar água ou similares;</div>
            <div class="term-item small-text">- Transitar do alojamento qualquer objeto e/ou equipamento pertencente a terceiros ou ao local;</div>
            <div class="term-item small-text">- Colocar cartazes e/ou similares nas paredes do alojamento;</div>
            <div class="term-item small-text">- Qualquer atitude que resulte em depredação ou dano ao local de alojamento;</div>
            <div class="term-item small-text">- A entrada de pessoas do sexo oposto, em qualquer dependência da Acadepol, inclusive nos alojamentos;</div>
        </div>

        <div class="page-break"></div>

        <div class="terms-content">
            <div class="term-item">3. Material danificado no ALOJAMENTO deverá ser comunicado imediatamente à administração da Acadepol e providenciado seu ressarcimento;</div>
            <div class="term-item">4. Entendendo-se que o ALOJAMENTO não comporta uma infraestrutura hoteleira, cabe a cada um dos seus ocupantes a arrumação diária de sua cama;</div>
            <div class="term-item">5. Os ocupantes de cada um dos dormitórios são responsáveis pelo controle das chaves de seus respectivos dormitórios;</div>
            <div class="term-item">6. Na ausência de seus ocupantes, todas as dependências do ALOJAMENTO devem permanecer trancadas;</div>
            <div class="term-item">7. A Acadepol não se responsabiliza pelos bens dos ocupantes do ALOJAMENTO, portanto cada ocupante é responsável por seus pertences;</div>
            <div class="term-item">8. Será cancelada a autorização de permanência no ALOJAMENTO ao ocupante que desrespeitar as normas deste Regulamento e/ou praticar atos que desabonem a conduta e os bons costumes.</div>
        </div>

        <table>
            <tr>
                <th width="25%">DATA CHECK-IN:</th>
                <th width="25%">HORA CHECK-IN:</th>
                <th width="30%">RESPONSÁVEL:</th>
                <th width="20%">MATRÍCULA:</th>
            </tr>
            <tr>
                <td style="height: 25px;"></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </table>

        <table>
            <tr>
                <th width="25%">DATA CHECK-OUT:</th>
                <th width="25%">HORA CHECK-OUT:</th>
                <th width="30%">RESPONSÁVEL:</th>
                <th width="20%">MATRÍCULA:</th>
            </tr>
            <tr>
                <td style="height: 25px;"></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
        </table>

        <table>
            <tr>
                <th>OBSERVAÇÕES:</th>
            </tr>
            <tr>
                <td style="height: 70px;"></td>
            </tr>
        </table>

        <div style="margin-top: 20px; text-align: center; display: flex; justify-content: space-between;">
            <div style="width: 45%;">
                <div class="signature-line">ASSINATURA DO ALOJADO</div>
            </div>
            <div style="width: 45%;">
                <div class="signature-line">ASS. DO RESP. CHECK-OUT</div>
            </div>
        </div>
    </div>
</body>
</html>