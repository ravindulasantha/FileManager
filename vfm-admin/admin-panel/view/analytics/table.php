<div class="row">
    <div class="col-md-12">
        <div class="card mb-3">
            <div class="card-header">
                <strong><?php echo $setUp->getString("actions"); ?></strong>
            </div>
            <div class="card-body">
                <div class="table-responsive small">
                    <table class="table statistics table-condensed" id="sortanalytics" width="100%">
                      <thead>
                          <tr>
                              <th><span class="sorta"><?php echo $setUp->getString("day"); ?></span></th>
                              <th><span>hh:mm:ss</span></th>
                              <th><span class="sorta"><?php echo $setUp->getString("user"); ?></span></th>
                              <th><span class="sorta"><?php echo $setUp->getString("action"); ?></span></th>
                              <th><span class="sorta"><?php echo $setUp->getString("type"); ?></span></th>
                              <th><span class="sorta"><?php echo $setUp->getString("item"); ?></span></th>
                          </tr>
                        </thead>
                        <tbody>
    <?php
    foreach ($logs as $log) {
        $logfile = '_content/log/'.basename($log);
        if (file_exists($logfile)) {
            $resultnew = json_decode(file_get_contents($logfile), true);
            $result = $resultnew ? array_merge($result, $resultnew) : array();
        }
    }
    $numup = 0;
    $numdel = 0;
    $numplay = 0;
    $numdown = 0;

    $polardowncount = 0;
    $polarplaycount = 0;
    $polarupcount = 0;

    $labelsarray = array();
    $updataset = array();
    $removedataset = array();
    $playdataset = array();
    $downloaddataset = array();

    foreach ($result as $key => $value) {
        $listtime = strtotime($key);
        $showtime = date($formatdate, $listtime);
        $labelsarray[] = $showtime;

        $uploads = 0;
        $removes = 0;
        $plays = 0;
        $downloads = 0;

        foreach ($value as $kiave => $day) {
            $contextual = "";

            $item = str_replace('\\\'', '\'', $day['item']);

            if ($day['action'] == 'ADD') {
                $uploads++;
                $numup++;
                $polarupcount++;
                $contextual = "bg-success bg-opacity-25";
            }
            if ($day['action'] == 'REMOVE') {
                $removes++;
                $numdel++;
                $contextual = "bg-danger bg-opacity-25";
            }
            if ($day['action'] == 'PLAY') {
                $plays++;
                $numplay++;
                $polarplaycount++;
                $contextual = "bg-warning bg-opacity-25";
            }
            if ($day['action'] == 'DOWNLOAD') {
                $downloads++;
                $numdown++;
                $polardowncount++;
                $contextual = "bg-info bg-opacity-25";
            } ?>
            <tr class="<?php echo $contextual; ?>">
            <td data-order="<?php echo $listtime; ?>"><?php echo $showtime; ?></td>
            <td><?php echo $day['time']; ?></td>
            <td><?php echo $day['user']; ?></td>
            <td><?php echo $setUp->getString(strtolower($day['action'])); ?></td>
            <td><?php echo $day['type']; ?></td>
            <td class="text-nowrap"><?php echo $item; ?></td>
            <?php
        }
        array_push($updataset, $uploads);
        array_push($removedataset, $removes);
        array_push($playdataset, $plays);
        array_push($downloaddataset, $downloads);
    }
    $updataset = array_reverse($updataset);
    $removedataset = array_reverse($removedataset);
    $playdataset = array_reverse($playdataset);
    $downloaddataset = array_reverse($downloaddataset);
    $labelsarray = array_reverse($labelsarray);
    ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td><?php echo $setUp->getString("day"); ?></td>
                                <td>hh:mm:ss</span></td>
                                <td><?php echo $setUp->getString("user"); ?></td>
                                <td><?php echo $setUp->getString("action"); ?></td>
                                <td><?php echo $setUp->getString("type"); ?></td>
                                <td><?php echo $setUp->getString("item"); ?></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$legendlabels = array(
    'add' => $setUp->getString('add'),
    'download' => $setUp->getString('download'),
    'remove' => $setUp->getString('remove'),
    'play' => $setUp->getString('play'),
);

$LOGvars = array(
    'uploads' => $updataset,
    'removes' => $removedataset,
    'plays' => $playdataset,
    'downloads' => $downloaddataset,
    'datalabels' => $labelsarray,
    'legendlabels' => $legendlabels,
    'numup' => $numup,
    'numdel' => $numdel,
    'numplay' => $numplay,
    'numdown' => $numdown,
); 
?>
<script type='text/javascript'>
/* <![CDATA[ */
var LOGvars = '<?php echo json_encode($LOGvars); ?>';
/* ]]> */
</script>
<div class="d-grid gap-2">
    <button type="button" class="btn btn-primary btn-lg mb-2" data-bs-toggle="modal" data-bs-target="#csv-modal"><i class="bi bi-filetype-csv"></i> <?php echo $setUp->getString("export"); ?> .csv</button>
</div>