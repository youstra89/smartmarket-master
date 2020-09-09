<!DOCTYPE html>
<html>
  <head>
    <style>
      @font-face {
        font-family: 'Elegance';
        font-weight: normal;
        font-style: normal;
        font-variant: normal;
        src: url("http://eclecticgeek.com/dompdf/fonts/Elegance.ttf") format("truetype");
      }

      /** Define the margins of your page **/
      @page {
        margin: 0px 0px;
      }

      table{
        padding-right: 5px;
      }

      .table {
        /* border: 1px solid black; */
        border-left-style: none;
        border-right-style: none;
      }
      
      .td {
        border-top-style: 1px black solid;
        border-bottom-style: 1px black solid;
        border-left-style: none;
        border-right-style: none;
      }
      
      .th {
        border-right-style: solid 5px black;
      }
      /* tr:nth-child(even) {background-color: #CCC}
      tr:nth-child(odd) {background-color: #FFF} */

      main{
        width: 340px;
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
    <title>Ticket de caisse N° <?php echo $commande->getReference() ?> | Smart Market</title>
  </head>
  <body onload="window.print()">
    <!-- Wrap the content of your PDF inside a main tag -->
    <main>
      <div class="content">
        <h1 class="title" style="font-size: 1.3em">
          {% if info is defined %}
            <strong>{{ info.nom }}</strong><br>
            {% if info.adresse is not empty %}
              ***** {{ info.adresse }} *****<br>
            {% endif %}
            {% if info.telephone is not empty %}
              Téléphone: {{ info.telephone }}<br>
            {% endif %}
            {% if info.email is not empty %}
              Email: {{ info.email }}<br>
            {% endif %}
            {% if info.siteWeb is not empty %}
              Site web: {{ info.siteWeb }}<br>
            {% endif %}
          {% else %}
            SmartMarket
          {% endif %}
          ******000******
        </h1>
        <br>
        <div class="row">
          <div class="info-enterprise" style="font-size: 1.5em">
            <div class="reference-commande">
              <table>
                <tr>
                  <td witdh="10%"><span class="ref">Réf. facture</span></td>
                  <td><span class="ref">: {{ commande.reference }}</span></td>
                </tr>
                <tr>
                  <td witdh="10%"><span class="ref">Code client</span></td>
                  <td><span class="ref">: {{ commande.customer.reference }}</span></td>
                </tr>
                <tr>
                  <td witdh="10%"><span class="ref">Client: </span></td>
                  <td><span class="ref">: {{ commande.customer.firstname }} {{ commande.customer.lastname }}</span></td>
                </tr>
              </table>
            </div>
          </div>
        </div><br>
        <div class="row">
          <p style="text-align: center; font-size: 1.5em">
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
        <div class="row" style="text-align: right; font-size: 1.3em; padding-right: 20px;">
          <span style="font-weight: bold;">{{ 'now'|date('d-m-Y') }}</span> à <span style="font-weight: bold;">{{ 'now'|date('H:i:s') }}</span>
        </div>
        <div class="table-responsive">
          {% set i = 0 %}
          <table class="table" cellspacing="0" width="100%" border="1">
            <thead>
              <tr align="left" style="font-size: 1.3em">
                <th class="td">Désignation</th>
                <th class="td">PU</th>
                <th class="th">Qte</th>
                <th class="td">Total</th>
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
                  <tr class="td" bgcolor="{{ color }}">
                    <td class="td" style="font-size: 1.1em">{{ item.product.label }}</td>
                    <td class="td" style="font-size: 1.1em">{{ item.unitPrice|number_format(0, ',', '.') }}</td>
                    <td class="th" style="font-size: 1.1em; text-align: center">{{ item.quantity }}</td>
                    <td class="td" style="font-size: 1.1em">{{ item.subtotal|number_format(0, ',', '.') }}</td>
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
            <table class="" cellspacing="0" border="0" width="100%" style="font-size: 1.3em">
              <tfoot>
                <tr style="border: 1px solid black">
                  <td width="50%">Montant HT</td>
                  <td width="50%" align="right" style="align: right">{{ commande.totalAmount|number_format(0, ',', '.') }} F&nbsp;</td>
                </tr>
                <tr style="border: 1px solid black">
                  <td width="50%">Remise</td>
                  <td width="50%" align="right" style="align: right">{{ commande.remise|number_format(0, ',', '.') }} F&nbsp;</td>
                </tr>
                <tr style="border: 1px solid black">
                  <td width="50%">Montant TTC</td>
                  <td width="50%" align="right" style="align: right">{{ commande.montantTtc|number_format(0, ',', '.') }} F&nbsp;</td>
                </tr>
                <tr style="border: 1px solid black; line-height: 40px; font-size: 2em">
                  <td width="50%">Net à payer</td>
                  <td width="50%" bgcolor="#D3D3D3" align="right" style="align: right; font-weight: bold">{{ commande.netAPayer|number_format(0, ',', '.') }} F&nbsp;</td>
                </tr>
                <tr style="border: 1px solid black">
                  <td width="50%">Somme versée</td>
                  <td width="50%" align="right" style="align: right">{{ settlements|last.amount|number_format(0, ',', ' ') }} F&nbsp;</td>
                </tr>
                <tr style="border: 5px solid black">
                  <td width="50%">Reste</td>
                  <td width="50%" align="right" style="align: right">
                    {% if commande.ended == true and resteAPayer == 0 %}
                      Soldée
                    {% else %}
                      {{ resteAPayer|number_format(0, ',', ' ') }} F
                    {% endif %}
                  &nbsp;
                  </td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
        <br>
        <hr style="width: 75%; float: right;"><br>
        <div class="row">
          <div style="text-align: right; font-style: italic; padding-right: 20px; font-size: 1.5em">
            <p><strong>Merci pour votre confiance</strong></p>
            <p>Edité par : <strong>{{ app.user.username }}</strong></p><br>
          </div>
        </div>
        <div class="row">
          {% if info is defined and info.adresse is not empty %}
            <br>
            <p style="text-align: center; font-weight: bold; font-style: italic; font-size: 1.5em">{{ info.slogan }}</p>
          {% endif %}
        </div>
      </div>
    </main>
  </body>
</html>