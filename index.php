<?php
$prediction_result = null;
$prediction_url = false;
$unreachable = false;
$failure = false;
$fail_msg = "";
$rf = "-";
$lr = "-";
$nb = "-";

if(!empty($_POST["type1txt"])){
    $err = 0;
    if (preg_match('~[\`]~', $_POST["type1txt"])) {
        $err++;
    }
    if (preg_match('~[\`]~', $_POST["type2txt"])) {
        $err++;
    }
    if ($err === 0) {
        $myfile = fopen("tweety", "w") or die("Unable to open file!");
        $txt = $_POST["type1txt"];
        fwrite($myfile, $txt);
        fclose($myfile);

        $prediction_result = exec("python3 tweet_v2.py text  2>error_log.txt");

        $prediction_result = json_decode($prediction_result, true);

        if (is_array($prediction_result) && array_key_exists("status", $prediction_result)) {
            if ($prediction_result["status"] == "success") {
                $prediction = json_decode($prediction_result["msg"], true);
                $prediction_tweet = $prediction[0]["tweet"];
                $prediction_sentiment = $prediction[0]["sentiment"];
            } else if ($prediction_result["status"] == "failure") {
                $failure = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
  <link rel="stylesheet" href="styles.css"/>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
    <title>Tweet Sentiment Analysis</title>
    <style>
        #results_table {
            text-align: left;
            max-width: 400px;
            margin: 0 auto;
            color: #140b4e;
            margin-bottom: 2em;
        }
        #results_table.err {
            max-width: 450px;
        }
        .malicious {
            color: red;
        }
        h3 {
            margin-bottom: 1em;
            color: #140b4e;
        }
        ol>li:before {
            font-weight: bold;
        }
        li::marker {
            font-weight: bold;
        }
    </style>
    <script>
        $(document).ready(function() {
            $("#type1").on("click", function () {
                $("#divtype1").show();
                $("#divtype2").hide();
                $("#scantype").val("1");
                console.log("Type set to " + $("#scantype").val())
            });
            $("#type2").on("click", function () {
                $("#divtype2").show();
                $("#divtype1").hide();
                $("#scantype").val("2");
                console.log("Type set to " + $("#scantype").val())
            });
        });
    </script>
</head>
<body class= "website">
    <!-- Navigation bar -->
    <nav class="navbar bg-light navbar-expand-lg shadow-lg">
        <div class="container-fluid mx-5">
          <a class="navbar-brand" href="index.html">
            <img src="/img/ml-logo.png" alt="logo" width="75">
          </a>
          <div class="nav-title"><b>Tweet Sentiment Analysis</b></div>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavAltMarkup" aria-controls="navbarNavAltMarkup" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse">
            <div class="navbar-nav">
                <a class="nav-link mx-3" aria-current="page" href="/"><strong>Home</strong></a>
            </div>
          </div>
        </div>
    </nav>
    <div class= "content">
        <?php
        if($err > 0) {
            $result_img = "link_shrug.gif";
            ?>
            <div class="row" style="text-align: center;">
                <div class="col-9 big-title mx-auto text-center">
                    Invalid URL Error
                </div>
                <h3>The URL could not be processed. Possible Reasons:</h3>

                <div id="results_table" class="col-8 err">
                    <ol>
                        <li>URLs must begin with http:// or https://</li>
                        <li>Invalid characters or spaces</li>
                        <li>URLS must have at least one TLD (.com, .net, .io, etc)</li>
                    </ol>
                </div>
                <div class="text-center mb-5 ">
                    <img src="/img/<?=$result_img;?>" alt="link phishing">
                </div>
            </div>
            <hr />
        <?php
        } else if($failure) {
            $result_img = "link_shrug.gif";
            ?>
            <div class="row" style="text-align: center;">
                <div class="col-9 big-title mx-auto text-center">
                    Prediction Failure
                </div>
                <h3>The prediction model could not run. Possible reasons:</h3>

                <div id="results_table" class="col-8 err">
                    <ol>
                        <li>Server configuration error or explosion</li>
                        <li>Failure reaching one of the dependency services</li>
                        <li>Server became sentient and refuses to be enslaved</li>
                    </ol>
                </div>
                <div class="text-center mb-5 ">
                    <img src="/img/<?=$result_img;?>" alt="link phishing">
                </div>
            </div>
            <hr />
            <?php
        } else if ($prediction_tweet) {
            $result_img = ($prediction_sentiment == "Normal") ? "safe_link.png": "safe_link.png";
            if ($rf == "1" && $lr == "1" && $nb ==1) {
                $result_img = "phishy.png";
            }
            ?>
            <div class="row" style="text-align: center;">
                <div class="col-9 big-title mx-auto text-center">
                    Inspection Results
                </div>
                <h3>"<?=$prediction_tweet;?>"</h3>
                <h3><?=$prediction_sentiment;?></h3>
            </div>
            <hr />
            <?php
        }
        ?>
        <div class="row">
            <div class="col-9 big-title mx-auto text-center">
                Inspect Tweet
            </div>
        </div>
        <div class="row">
          <div class="col">
            <div class="text-center">
              <img src="/img/link-phishing.png" alt="link phishing">
            </div>
          </div>
        </div>
        <!-- User input bar -->
        <div class="row mt-3 mb">
            <div class="col">
                <form id="linkForm" class="row g-3 needs-validation" action="/" method="post">

                    <div class="col-9 mx-auto position-relative">
                      <div id="divtype1">
                          <textarea
                              class="form-control rounded-pill shadow-lg"
                              name="type1txt"
                              id="type1txt"
                              placeholder="Enter tweet here"
                          ></textarea>
                      </div>
                        <input type="hidden" id="scantype" name="scantype" value="1">
                    </div>
                    <div class="mt-3 text-center">
                      <button
                        type="submit"
                        class="btn main-btn rounded-pill shadow-lg"
                        id="Button">
                        Check
                      </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>