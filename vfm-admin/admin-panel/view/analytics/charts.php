<div class="row">
    <div class="col-12">
        <div class="card mb-3">
            <div class="card-header">
                <strong><?php print $setUp->getString("main_activities"); ?></strong>
            </div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-sm-6">
                        <div class="list-group mb-3" id="mainLegend"></div>
                    </div>

                    <div class="col-sm-6 col-md-6 col-lg-4 col-xl-3">
                        <div class="canvas-holder">
                            <canvas class="chart" id="pie" width="400" height="400"></canvas>
                            <div class="showdata"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="row">
            <?php
            if ($range && $range > 1) { ?>
                <div class="col-12" id="chart-ranger">
                    <div class="card mb-3"> 
                        <div class="card-header with-border">
                            <i class="bi bi-graph-up"></i> 
                            <strong><?php print $setUp->getString("trendline"); ?></strong>
                        </div>
                        <div class="canvas-range-holder">
                            <canvas class="chart" id="ranger" width="800" height="300"></canvas>
                        </div>
                    </div>
                </div>
                <?php
            }
            ?>
        </div> <!-- row -->
    </div>
</div> <!-- row -->
