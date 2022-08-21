<?php

require_once dirname(__FILE__).'/class.vfmupdater.php';
require_once dirname(__FILE__).'/Parsedown.php';
?>
<div class="row">
    <div class="col-xl-6">

<h4>Current version: <?php echo VFM_VERSION; ?></h4>
<p class="small">Last checked on <?php echo date('l jS \of F Y h:i:s A'); ?>. <a class="text-primary" href="javascript:window.location.reload(true)"><u>Check Again</u></a>.</p>
<?php
$updates = VFM_updater()->checkUpdates();

$response = $updates ? json_decode($updates, true) : false;
$messages = ($response && isset($response['messages'])) ? $response['messages'] : array();
foreach ($messages as $message) {
    echo '<div class="mb-3 fw-bold">'.$message.'</div>';
}
$updateclass = 'd-none';
$licenseclass = 'is-invalid';
if (isset($response['latest']) && $response['latest'] == 1) {
    // $licenseclass = 'is-valid';
}

if (isset($response['license']) && $response['license'] == 1) {
    $updateclass = '';
    $licenseclass = 'is-valid';
    $Parsedown = new Parsedown();
    echo '<div class="p-3 border mb-3 small">'. $Parsedown->text($response['logs']).'</div>';
}
?>
        <div class="<?php echo $updateclass; ?>">
            <div class="shadow bg-white p-3 mb-3 border border-4 border-warning border-end-0 border-top-0 border-bottom-0">Important: Before updating, please back up your <code>/vfm-admin/</code> directory.</div>
            <div class="mb-3"><button type="button" class="btn btn-primary start-update"><i class="bi bi-arrow-right-short"></i> Start update</button></div>
            <div class="update-output mb-3"></div>
            <div class="coundown mb-3"></div>
        </div>

    </div>
    <div class="col-xl-6">
        <div class="card mb-3">
             <div class="card-header">
                License
            </div>
            <div class="card-body purchase-key-group">
                <form role="form" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>?section=updates&action=update">
                <div class="input-group mb-3">
                    <label for="purchase-code" class="input-group-text">Purchase code</label>
                    <input type="text" class="form-control fake-key <?php echo $licenseclass; ?>" placeholder="XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXX">
                    <input type="text" class="form-control <?php echo $licenseclass; ?>" id="purchase-code" name="license_key" placeholder="XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXX">
                    <button disabled type="submit" class="btn btn-primary update-purchase-code"><i class="bi bi-arrow-repeat"></i></button>
                </div>
                <div><a class="btn btn-outline-primary btn-sm" target="_blank" href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-"><i class="bi bi-key-fill"></i> Find my key</a></div>
                </form>
            </div>
        </div>
        <div class="card mb-3">
            <div class="card-header">
                Server requirements
            </div>
            <div class="card-body">
                <ul class="list-group rounded-0 border-0">
<?php
$serverCheck = VFM_updater()->serverCheck();
foreach ($serverCheck['details'] as $value) {
    $checkclass = $value['enabled'] == true ? ' bg-opacity-25 bg-success' : ' bg-opacity-25 bg-danger';
    $checkicon = $value['enabled'] == true ? '<i class="bi bi-check"></i>' : '<i class="bi bi-x"></i>';
    ?>
                    <li class="list-group-item border border-white<?php echo $checkclass; ?>"><?php echo $checkicon; ?> <?php echo $value['text']; ?></li>
    <?php
}
?>   
                </ul>
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card update-output-error d-none">
            <div class="card-header"><i class="bi bi-exclamation-circle"></i> Error log</div>
            <div class="card-body bg-danger bg-opacity-25">
                <pre class="small update-output-error" style="max-height: 400px;"></pre>
            </div>
        </div>
    </div>     
</div>

<style type="text/css">
    .purchase-key-group .fake-key{
        display: block;
    }
    .purchase-key-group #purchase-code{
        display: none;
    }
    .purchase-key-group:hover .fake-key{
        display: none;
    }
    .purchase-key-group:hover #purchase-code{
        display: block;
    }
</style>
<script type="text/javascript">
/**
 * VFMupdater plugin
 */
var VFMupdater = (function(){
    "use strict";

    function updateOutput(message){
        const wrapper = document.querySelector('.update-output');        
        wrapper.innerHTML += message + '<br>';
    }

    function updateOutputError(message){
        const wrapper = document.querySelector('.update-output-error');
        const content = wrapper.querySelector('pre');
        wrapper.classList.remove('d-none');
        content.innerHTML += message + '\n';
    }

    function actionCallback(response = false, callback = false){
        if (response) {
            if (response.error) {
                if (Array.isArray(response.error)) {
                    for(var error of response.error){
                        updateOutputError(error);
                    }
                } else {
                    updateOutputError(response.error);
                }
                // action('end');
            }
            if (response.result) {
                updateOutput(response.result);
                if (callback) {
                    setTimeout(function() {
                        callback();
                    }, 1000);
                }
            } else {
                console.log(response);
                // action('end');
                action('end');
                updateOutput('Update aborted');
            }
        }
    }

    function action(action = false, callback = false){
        if (action) {
            var formData = new FormData();
            formData.append("action", action);
            var xhr = new XMLHttpRequest();
            xhr.responseType = 'json';
            xhr.open("POST", 'admin-panel/updater/ajax.php', true);
            xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");

            xhr.onreadystatechange = function() { // Call a function when the state changes.
                if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
                    if (action == 'getkey') {
                        toggleKey(this.response);
                    } else {
                        actionCallback(this.response, callback);                        
                    }
                }
            };
            xhr.send(formData);
        }
    }

    function downloadPackage(){
        action('download', expandPackage);
    }

    function expandPackage(){
        action('expand', preparePackage);
    }

    function preparePackage(){
        action('prepare', replaceFiles);
    }

    function replaceFiles(){
        action('replace', endProcess);
    }

    function endProcess(){
        // action('end', reloadPage);
        action('end');

    }

    function reloadPage(){
        var timeleft = 10;
        var downloadTimer = setInterval(function(){
          if(timeleft <= 0){
            clearInterval(downloadTimer);
            window.location.reload(true);
          } else {
            document.querySelector(".coundown").innerHTML = "Reload page in " + timeleft + " seconds";
          }
          timeleft -= 1;
        }, 1000);
    }

    function toggleKey(key){
        if (key) {
            var key_input = document.querySelector('#purchase-code');
            key_input.value = key;
        }
    }

    function init(){
        var startupdate = document.querySelector('.start-update');
        startupdate.addEventListener('click', function(e){
            this.classList.add('d-none');
            downloadPackage();
        });
        var update_btn = document.querySelector('.update-purchase-code');
        var key_input = document.querySelector('#purchase-code');
        key_input.addEventListener('input', function(e){
            update_btn.disabled = false;
        });

        var fake_key = document.querySelector('.fake-key');

        key_input.addEventListener('focus', function(e){
            fake_key.classList.add('d-none');
            key_input.classList.add('d-block');
        });
        key_input.addEventListener('blur', function(e){
            fake_key.classList.remove('d-none');
            key_input.classList.remove('d-block');
        });
        action('getkey');
    }

    return {
        init
    };
}());

VFMupdater.init();
</script>