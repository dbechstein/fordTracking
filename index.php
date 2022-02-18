<?php

require_once('fordapi.php');
$vinResult = "";
if (isset($_POST['ordernumber'])) {

    $fordapi = new FordAPI('deu', $_POST['email'], $_POST['password']);
    $fordapi->DisableSSLCheck();

    $fordapi->Connect();
    $token = $fordapi->GetToken();

    $fordapi = new FordAPI('deu', $_POST['email'], $_POST['password'], $token->AccessToken, $token->RefreshToken, $token->Expires);
    $fordapi->DisableSSLCheck();
    $fordapi->Connect();

    $reservationData = $fordapi->getVIN($_POST['ordernumber']);
    $vinResult = $reservationData->result->entries[0]->product->vin;
}
if (!empty($_GET["vin"]) || !empty($vin)) {
    $cURLConnection = curl_init('https://rs-analytics-prod.apps.pd01e.edc1.cf.ford.com/api/v1/vehicleData');
    curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, $postRequest);
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);
    if (!empty($vin)) {
        $vin = "VIN: " . strtoupper($vinResult);
    } else {
        $vin = "VIN: " . str_replace("+", "", strtoupper($_GET["vin"]));
    }
    curl_setopt($cURLConnection, CURLOPT_HTTPHEADER, array(
        $vin
    ));

    $apiResponse = curl_exec($cURLConnection);
    curl_close($cURLConnection);

    $carData = json_decode($apiResponse, true);

    $updateYear = $carData['vinFeature']['updateDate'][0];
    $updateMonth = $carData['vinFeature']['updateDate'][1];
    $updateDay = $carData['vinFeature']['updateDate'][2];
    $updateHour = $carData['vinFeature']['updateDate'][3];
    $updateMinutes = $carData['vinFeature']['updateDate'][4];
    $updateSeconds = $carData['vinFeature']['updateDate'][5];

    $updateMonth = strlen($updateMonth) == 1 ? "0" . $updateMonth : $updateMonth;
    $updateDay = strlen($updateDay) == 1 ? "0" . $updateDay : $updateDay;
    $updateHour = strlen($updateHour) == 1 ? "0" . $updateHour : $updateHour;
    $updateMinutes = strlen($updateMinutes) == 1 ? "0" . $updateMinutes : $updateMinutes;
    $updateSeconds = strlen($updateMinutes) == 1 ? "0" . $updateSeconds : $updateSeconds;
    $updateTime = $updateYear . "-" . $updateMonth . "-" . $updateDay . "T" . $updateHour . ":" . $updateMinutes . ":" . $updateSeconds;

    $lifeCycleMode = $carData['tappsResponseTemplate']['lifeCycleModeStatus']['lifeCycleMode'];
    $vehicleDateTime = $carData['tappsResponseTemplate']['lifeCycleModeStatus']['vehicleDateTime'];
    $tappsDateTime = $carData['tappsResponseTemplate']['lifeCycleModeStatus']['tappsDateTime'];

    $vehicleDateTime = date('d. M. Y h:i:s', strtotime($vehicleDateTime));
    $tappsDateTime = date('d. M. Y h:i:s', strtotime($tappsDateTime));
    $updateTime = date('d. M. Y h:i:s', strtotime($updateTime));


    $fordapi = new FordAPI('deu', 'username', 'password');
    $fordapi->DisableSSLCheck();

    $fordapi->Connect();
    $token = $fordapi->GetToken();


    $fordapi = new FordAPI('deu', 'username', 'password', $token->AccessToken, $token->RefreshToken, $token->Expires);
    $fordapi->DisableSSLCheck();
    $fordapi->Connect();

    $status = $fordapi->Status(str_replace(" ", "", strtoupper($_GET["vin"])));

    $cURLConnection = curl_init();

    curl_setopt($cURLConnection, CURLOPT_URL, 'https://www.digitalservices.ford.com/fs/api/v2/vehicles/image/full?vin=' . str_replace("+", "", strtoupper($_GET["vin"])) . '&year=2022&countryCode=DEU');
    curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);

    $picture = curl_exec($cURLConnection);
    curl_close($cURLConnection);

    $link = 'https://www.digitalservices.ford.com/fs/api/v2/vehicles/image/full?vin=' . str_replace(" ", "", strtoupper($_GET["vin"])) . '&year=2022&countryCode=DEU';
}
?>

<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="David Bechstein">
    <meta name="generator" content="">
    <title>Ford Status Tracker (DE/CH/AT)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-9792488914849319"
     crossorigin="anonymous"></script>
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=G-2H94ZV02LW"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'G-2H94ZV02LW');
</script>
</head>
<body>

<div class="container">
    <div class="row mt-3 mb-3">
        <div class="col-xs-12 col-sm-6">
            <div class="col-12 mb-3">
                <div class="card">
                    <div class="card-header">VIN Prüfer</div>
                    <div class="card-body">
				<span>Hier kannst du mit deinen Ford-Zugangsdaten und deiner Bestellnummer prüfen ob bereits eine VIN/FIN hinterlegt ist</span>
                        <form class="row g-3 mt-1" method="post">
                            <div class="col-xs-8 col-sm-9">
                                <input type="email" name="email" class="form-control form-control-dark"
                                       placeholder="E-Mail" aria-label="email" required="required">
                            </div>
                            <div class="col-xs-8 col-sm-9">
                                <input type="password" name="password" class="form-control form-control-dark"
                                       placeholder="Passwort" aria-label="email" required="required">
                            </div>
                            <div class="col-xs-8 col-sm-9">
                                <input type="text" name="ordernumber" class="form-control form-control-dark"
                                       placeholder="Bestellnummer" aria-label="Ordernumber" required="required">
                            </div>
                            <div class="col-xs-4 col-sm-3">
                                <button class="btn btn-primary" style="width: 100%" type="submit">Absenden</button>
                            </div>
                        </form>
                    </div>
                    <?php if (!empty($vinResult)) { ?>
                        <div class="card-footer">
                            <?php echo $vinResult; ?>
                        </div>
                    <?php } else { ?>

                        <div class="card-footer">
                            <?php if (isset($_POST['ordernumber'])) { ?>
                                <span style="color: red">Es wurde noch keine VIN/FIN hinterlegt</span>
                            <?php } ?>
                        </div>
                    <?php } ?>
                </div>
            </div>
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="col-xs-12 col-sm-8">
                            Du möchtest den Tracker unterstützen?
                        </div>
                        <div class="col-xs-12 col-sm-4">
                            <div id="donate-button-container">
                                <div id="donate-button"></div>
                                <script src="https://www.paypalobjects.com/donate/sdk/donate-sdk.js"
                                        charset="UTF-8"></script>
                                <script>
                                    PayPal.Donation.Button({
                                        env: 'production',
                                        hosted_button_id: '64UWJ6NRU5K9Q',
                                        image: {
                                            src: 'https://www.paypalobjects.com/de_DE/DE/i/btn/btn_donateCC_LG.gif',
                                            alt: 'Spenden mit dem PayPal-Button',
                                            title: 'PayPal - The safer, easier way to pay online!',
                                        }
                                    }).render('#donate-button');
                                </script>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xs-12 col-sm-6">
            <div class="col-12 mb-3">
                <div class="card">
                    <div class="card-body">
                        <form class="row g-3" method="get">
                            <div class="col-xs-8 col-sm-9">
                                <input type="search" name="vin" class="form-control form-control-dark"
                                       placeholder="FIN/VIN..." aria-label="Search">
                            </div>
                            <div class="col-xs-4 col-sm-3">
                                <button class="btn btn-primary" style="width: 100%" type="submit">Absenden</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <?php if (!empty($_GET["vin"])) { ?>
                <div class="col-12">
                    <div class="card rounded-3 shadow-sm">
                        <div class="card-header py-3">Ford Status Tracker (DE/CH/AT)</div>
                        <div class="card-body">
                            <img style="-webkit-user-select: none;margin: auto;cursor: zoom-in;background-color: hsl(0, 0%, 90%);transition: background-color 300ms; width: 100%;"
                                 src="<?php echo $link; ?>">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item"><b>LifeCycleMode:</b> <?php echo $lifeCycleMode; ?></li>
                                <li class="list-group-item"><b>VehicleDateTime:</b> <?php echo $vehicleDateTime; ?></li>
                                <li class="list-group-item"><b>TappsDateTime:</b> <?php echo $tappsDateTime; ?></li>
                                <li class="list-group-item"><b>UpdateTime:</b> <?php echo $updateTime; ?></li>
                                <li class="list-group-item">
                                    <b>Vmacs2CharCode:</b> <?php echo $status->result->result->order->vmacs2CharCode; ?>
                                </li>
                                <li class="list-group-item">
                                    <b>Vmacs3CharCode:</b> <?php echo $status->result->result->order->vmacs3CharCode; ?>
                                </li>
                                <li class="list-group-item">
                                    <b>VmacsStatusDesc:</b> <?php echo $status->result->result->order->vmacsStatusDesc; ?>
                                </li>
                                <li class="list-group-item">
                                    <b>vmacsStatusDate:</b> <?php echo date('d. M. Y h:i:s', strtotime($status->result->result->order->vmacsStatusDate)); ?>
                                </li>
                                <li class="list-group-item">
                                    <b>GobStatusCode:</b> <?php echo $status->result->result->order->gobStatusCode; ?></li>
                                <li class="list-group-item">
                                    <b>GobStatusDesc:</b> <?php echo $status->result->result->order->gobStatusDesc; ?></li>
                                <li class="list-group-item">
                                    <b>GobStatusDate:</b> <?php echo date('d. M. Y h:i:s', strtotime($status->result->result->order->gobStatusDate)); ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>
<footer class="footer mt-auto py-3 bg-light">
  <div class="container">
    <a class="pr-3" href="impressum.html">Impressum</a>
    <a href="datenschutz.html">Datenschutz</a>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
        crossorigin="anonymous"></script>
<script src="https://app.enzuzo.com/apps/enzuzo/static/js/__enzuzo-cookiebar.js?uuid=167ab356-90a5-11ec-a547-83ed978bef36"></script>
</body>
</html>