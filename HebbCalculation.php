<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

$networkData = new \stdClass();

$networkData->xi = new \stdClass();
$networkData->target = new \stdClass();
$networkData->dellWeights = new \stdClass();
$networkData->dellWeightsBias = new \stdClass();
$networkData->weights = new \stdClass();
$networkData->weightsBias = new \stdClass();


//get posted data
$networkInput = json_decode(file_get_contents("php://input"));

$xi = $networkInput->body;
$target = array();

$twoDTargetToSend = array(array());
$weightsToSend = array(array());
$twoDWeightsBiasToSend = array(array());
$dellWeightsToSend = array(array());
$dellWeightsBiasToSend = array(array());

for($i=0; $i<count($networkInput->target); $i++ )
{
    array_push($target, $networkInput->target[$i][0]);
}
$bias = $networkInput->bias;
$numberOfSets = count($xi);

$weightsBias[0]=0;
$break=0;

for($i=0; $i<count($xi[0]); $i++)
{
    $weights[0][$i] = 0;
}
$numberOfInputs = count($xi[0]);

for($set=1; $set<=$numberOfSets; $set++)
{
    if($numberOfInputs != count($xi[$set-1]))
    {
        $break=1;
        break;
    }
    
    for($input=0; $input<$numberOfInputs; $input++)
    {
        $dellWeights[$set][$input] = $xi[$set-1][$input]*$target[$set-1];
        $weights[$set][$input] = $weights[$set-1][$input] + $dellWeights[$set][$input];
        $dellWeightsBias[$set] = $bias*$target[$set-1];
        $weightsBias[$set] = $weightsBias[$set-1] + $dellWeightsBias[$set];

        $twoDTargetToSend[$set-1][0] = $target[$set-1];
        $weightsToSend[$set-1][$input] = $weights[$set][$input];
        $twoDWeightsBiasToSend[$set-1][0] = $weightsBias[$set];
        $dellWeightsToSend[$set-1][$input] = $dellWeights[$set][$input];
        $dellWeightsBiasToSend[$set-1][0] = $dellWeightsBias[$set];
    }
}


$networkData->xi->values = $xi;
$networkData->xi->columnName = 'x';

$networkData->target->values = $twoDTargetToSend;
$networkData->target->columnName = 't';

$networkData->dellWeights->values = $dellWeightsToSend;
$networkData->dellWeights->columnName = '&Delta;w';

$networkData->dellWeightsBias->values = $dellWeightsBiasToSend;
$networkData->dellWeightsBias->columnName = '&Delta;wb';

$networkData->weights->values = $weightsToSend;
$networkData->weights->columnName = 'w';

$networkData->weightsBias->values = $twoDWeightsBiasToSend;
$networkData->weightsBias->columnName = 'wb';

$allEpochs = array();
array_push($allEpochs, $networkData);

$networkDataToSend = new \stdClass();

$networkDataToSend->tables = $allEpochs;
$networkDataToSend->isRecurring = false;

echo json_encode($networkDataToSend);

?>
