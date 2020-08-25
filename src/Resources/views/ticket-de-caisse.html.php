<!DOCTYPE html>
<html>
    <head>
        <style>
            /* body{
                size: auto;
                margin: 0mm;  
                width: 240px
            } */

            @font-face {
                font-family: 'Elegance';
                font-weight: normal;
                font-style: normal;
                font-variant: normal;
                src: url("http://eclecticgeek.com/dompdf/fonts/Elegance.ttf") format("truetype");
            }

            /** Define the margins of your page **/
            @page {
              margin: 0px 10px;
            }

            table{
              width: 98%;
              padding-right: 5px;
            }

            table, th, td {
              /* border: 1px solid black; */
            }

            /* tr:nth-child(even) {background-color: #CCC}
            tr:nth-child(odd) {background-color: #FFF} */

            main{
              width: 300px;
              font-size: .7em
            }

            .numero{
              text-align: center;
            }

            .ref{
              font-style: underline;
              /* font-size: .8em; */
            }

            .info-customer{
              width: 40%;
              display: inline-block;
              float: right;
              vertical-align : top;
              margin-top: 1px;
            }

            .row{
              margin: 0px;
              padding: 0px;
              width: 100%;
            }

            .col{
              width: 32%;
              display: inline-block;
            }

            p{
              margin: 0px;
              padding: 5px;
            }

            .info{
              font-weight: bold;
            }

            tbody{
              /* font-size: .7em; */
            }

            .title{
              text-align: center;
              font-family: 'Elegance';
              font-size: 1em;
              padding-top: 10px;
              margin-bottom: 0px;
              padding-bottom: 0px;
            }
        </style>
        <meta charset="UTF-8">
        <title>Ticket de caisse N° <?= $commande->getReference() ?> | Smart Market</title>
    </head>
    <body onload="window.print()">
        <!-- Wrap the content of your PDF inside a main tag -->
        <main>
          <div class="content">
            <h1 class="title">
                <?php if (!empty($info)): ?>
                    <strong><?= $info->getNom() ?></strong><br>
                    <?php if (!empty($info->getAdress())): ?>
                        ******* <?= $info->getAdresse() ?> *******<br>
                    <?php endif; ?>
                    <?php if (!empty($info->getTelephone())): ?>
                        Téléphone: <?= $info->getTelephone() ?><br>
                    <?php endif; ?>

                    <?php if (!empty($info->getEmail())): ?>
                        Email: <?= $info->getEmail() ?><br>
                    <?php endif; ?>

                    <?php if (!empty($info->getSiteWeb())): ?>
                        Site web: <?= $info->getSiteWeb() ?><br>
                    <?php endif; ?>
                <?php else: ?>
                    SmartMarket
                <?php endif; ?>
              ******000******
            </h1>
            <br>
            <div class="row">
              <div class="info-enterprise" style="">
                <div class="reference-commande">
                  <table>
                    <tr>
                      <td witdh="10%"><span class="ref">Réf. facture</span></td>
                      <td><span class="ref">: <?= $commande->getreference() ?></span></td>
                    </tr>
                    <tr>
                      <td witdh="10%"><span class="ref">Code client</span></td>
                      <td><span class="ref">: <?= $commande->getCustomer()->getReference() ?></span></td>
                    </tr>
                    <tr>
                      <td witdh="10%"><span class="ref">Client: </span></td>
                      <td><span class="ref">: <?= $commande->getcustomer()->getFirstname() ?> <?= $commande->getcustomer()->getlastname() ?></span></td>
                    </tr>
                  </table>
                </div>
              </div>
            </div><br>
            <div class="row">
              <p style="text-align: center;">
                {% if settlements|last.modePaiement == 1 %}
                  PAIEMENT AU COMPTANT
                {% elseif settlements|last.modePaiement == 2 %}
                  PAIEMENT PAR VIREMENT
                {% elseif settlements|last.modePaiement == 3 %}
                  PAIEMENT AVEC ACOMPTE CLIENT
                {% elseif settlements|last.modePaiement == 4 %}
                  PAIEMENT PAR SERVICE MONEY
                {% endif %}
              </p>
            </div>
            <div class="row" style="text-align: right; padding-right: 10px;">
              <span style="font-weight: bold;">{{ 'now'|date('d-m-Y') }}</span> à <span style="font-weight: bold;">{{ 'now'|date('H:i:s') }}</span>
            </div>
            <div class="table-responsive">
              {% set i = 0 %}
              <table class="table" cellspacing="0" border="0">
                <thead>
                  <tr align="left">
                    <th>Désignation</th>
                    <th>PU</th>
                    <th>Qte</th>
                    <th>Total</th>
                  </tr>
                </thead>
                {% for item in commande.product %}
                  {% set i = i + 1 %}
                  {% set color = "" %}
                  {% if (i % 2) == 0 %}
                      {% set color = 'white' %}
                  {% else %}
                      {% set color = '#EEEEEE' %}
                  {% endif %}
                    <tbody>
                      <tr bgcolor="{{ color }}">
                        <td>{{ item.product.label }}</td>
                        <td style="font-size: .9em">{{ item.unitPrice|number_format(0, ',', '.') }} F</td>
                        <td style="text-align: center">{{ item.quantity }}</td>
                        <td style="font-size: .9em">{{ item.subtotal|number_format(0, ',', '.') }} F</td>
                      </tr>
                    </tbody>
                {% endfor %}
              </table>
              {% set totalPaye = 0 %}
              {% for item in settlements %}
                {% set totalPaye = totalPaye + item.amount %}
              {% endfor %}

              {# Ensuite, on détermine le reste à payer #}
              {% set resteAPayer = commande.netAPayer - totalPaye %}
              <br>
              <div style="">
                <table class="table" cellspacing="0" border="0" width="90%">
                  <tfoot>
                    <tr style="border: 1px solid black">
                      <td width="50%">Montant HT</td>
                      <td width="40%" align="right" style="align: right">{{ commande.totalAmount|number_format(0, ',', '.') }} F&nbsp;</td>
                      <td width="5%"></td>
                    </tr>
                    <tr style="border: 1px solid black">
                      <td width="50%">Remise</td>
                      <td width="40%" align="right" style="align: right">{{ commande.remise|number_format(0, ',', '.') }} F&nbsp;</td>
                      <td width="5%"></td>
                    </tr>
                    <tr style="border: 1px solid black">
                      <td width="50%">Montant TTC</td>
                      <td width="40%" align="right" style="align: right">{{ commande.montantTtc|number_format(0, ',', '.') }} F&nbsp;</td>
                      <td width="5%"></td>
                    </tr>
                    <tr style="border: 1px solid black">
                      <td width="50%">Net à payer</td>
                      <td width="40%" bgcolor="#D3D3D3" align="right" style="align: right">{{ commande.netAPayer|number_format(0, ',', '.') }} F&nbsp;</td>
                      <td width="5%"></td>
                    </tr>
                    <tr style="border: 1px solid black">
                      <td width="50%">Somme versée</td>
                      <td width="40%" align="right" style="align: right">{{ settlements|last.amount|number_format(0, ',', ' ') }} F&nbsp;</td>
                      <td width="5%"></td>
                    </tr>
                    <tr style="border: 1px solid black">
                      <td width="50%">Reste</td>
                      <td width="40%" align="right" style="align: right">
                        {% if commande.ended == true and resteAPayer == 0 %}
                          Soldée
                        {% else %}
                          {{ resteAPayer|number_format(0, ',', ' ') }} F
                        {% endif %}
                      &nbsp;
                      </td>
                      <td width="5%"></td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
            <div class="row">
              <div style="text-align: right; font-style: italic; padding-right: 20px">
                <p><strong>Merci pour votre confiance</strong></p><br>
                <p>Edité par : <strong>{{ app.user.username }}</strong></p><br><br>
              </div>
            </div>
          </div>
        </main>
    </body>
</html>