<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
header('Access-Control-Max-Age: 1000');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

$networkInput = json_decode(file_get_contents("php://input"));

$xi = $networkInput->body;
$target = array();
$y = array();
$yin = array();

$xi=$networkInput->body;

$bias = $networkInput->bias;
$numberOfSets = count($xi);

$target=array();
for($i=0; $i<count($networkInput->target); $i++ )
{
    array_push($target, $networkInput->target[$i][0]);
}

$defaultWeight=$networkInput->defaultWeight;
$y=0;

$alpha = doubleval($networkInput->alpha);
$theta=doubleval($networkInput->theta);
$activationFunction=NULL;

if(count($target) == $numberOfSets)
{
    $networkData = calculatePerceptronWeights($xi, $target, $defaultWeight,$bias, $alpha, $theta, $numberOfSets, $activationFunction);
    $networkDataToSend = new \stdClass();
    $networkDataToSend->tables = $networkData[0];
    $networkDataToSend->isRecurring = $networkData[1];
    echo json_encode($networkDataToSend);

}

function calculatePerceptronWeights($xi, $target, $defaultWeight, $bias, $alpha, $theta, $numberOfSets, $activationFunction)
{
    $allEpochs = array();
    
    $networkData = new \stdClass();

    $networkData->yin = new \stdClass();
    $networkData->y = new \stdClass();
    $networkData->dellWeights = new \stdClass();
    $networkData->dellWeightsBias = new \stdClass();
    $networkData->weights = new \stdClass();
    $networkData->weightsBias = new \stdClass();
    
    $isRecurring = false;

    $weightsToSend = array(array());
    $twoDWeightsBiasToSend = array(array());
    $dellWeightsToSend = array(array());
    $twoDDellWeightsBiasToSend = array(array());
    $twoDYinToSend = array(array());
    $twoDYToSend = array(array());

    $numberOfInputs=count($xi[0]);
    $weightsBias[0]= $defaultWeight;
    $weightsEqualAre=0;
    $epoch=0;
    $break=0;
    
    for($i=0; $i<$numberOfInputs; $i++)
    {
        $weights[0][$i] = $defaultWeight;
    }

        do
        {
            $weightsEqualAre = 0;
            $epoch++;

            for($set=0;$set<$numberOfSets;$set++)
            {
                if($numberOfInputs != count($xi[$set]))
                {
                    $break=1;
                    break;
                }
                
                $yin[$set]=$weightsBias[$set];

                for($l=0;$l<$numberOfInputs;$l++)
                {
                    $yin[$set] = $yin[$set] + ($xi[$set][$l]*$weights[$set][$l]);
                }
                $twoDYinToSend[$set][0] = $yin[$set];
                
                if($yin[$set] < ($theta*-1))
                {
                    $y[$set] = -1;
                }
                else if((-1*$theta)<=$yin[$set] && $yin[$set]<=$theta)
                {
                    $y[$set] = 0;
                }
                else if($yin[$set]>$theta)
                {
                    $y[$set] = 1;
                }
                $twoDYToSend[$set][0] = $y[$set];

                if($y[$set] != $target[$set])
                {
                    for($input=0;$input<$numberOfInputs;$input++)
                    {   
                        $dellWeights[$set][$input] = $xi[$set][$input]*$alpha*$target[$set];
                        $dellWeightsToSend[$set][$input] = $dellWeights[$set][$input];
                        
                        $weights[$set+1][$input] = $weights[$set][$input] +  $dellWeights[$set][$input];
                        $weightsToSend[$set][$input] = $weights[$set+1][$input];
                    }

                    $dellWeightsBias[$set] = $alpha*$target[$set];
                    $twoDDellWeightsBiasToSend[$set][0] = $dellWeightsBias[$set];

                    $weightsBias[$set+1] = $weightsBias[$set] + $dellWeightsBias[$set];
                    $twoDWeightsBiasToSend[$set][0] = $weightsBias[$set+1];
                }
                else
                {
                    $weightsEqualAre++;
                    for($input=0;$input<$numberOfInputs;$input++)
                    {   
                        $dellWeights[$set][$input] = 0;
                        $dellWeightsBias[$set] = 0;

                        $twoDDellWeightsBiasToSend[$set][0] = $dellWeightsBias[$set];
                        $dellWeightsToSend[$set][$input] = $dellWeights[$set][$input];
                        
                        $weights[$set+1][$input] = $weights[$set][$input] +  $dellWeights[$set][$input];
                        $weightsToSend[$set][$input] = $weights[$set+1][$input];
                    }
                    $weightsBias[$set+1] = $weightsBias[$set] + $dellWeightsBias[$set];
                    $twoDWeightsBiasToSend[$set][0] = $weightsBias[$set+1];
                }
            }
            $weightsBias[0] = $weightsBias[$numberOfSets];
            $weights[0] = $weights[$numberOfSets];
            $ep = $epoch;

            $networkData->dellWeights->values = $dellWeightsToSend;
            $networkData->dellWeights->columnName = '&Delta;w';

            $networkData->dellWeightsBias->values = $twoDDellWeightsBiasToSend;
            $networkData->dellWeightsBias->columnName = '&Delta;wb';

            $networkData->weights->values = $weightsToSend;
            $networkData->weights->columnName = 'w';

            $networkData->weightsBias->values = $twoDWeightsBiasToSend;
            $networkData->weightsBias->columnName = 'wb';

            $networkData->y->values = $twoDYToSend;
            $networkData->y->columnName = 'y';

            $networkData->yin->values = $twoDYinToSend;
            $networkData->yin->columnName = 'yin';

            array_push($allEpochs, $networkData);

            if($epoch >= 10)
            {
                $isRecurring = true;
                break;
            }
            
            if($break)
            {
                break;
            }
            
            
        }while($weightsEqualAre != $numberOfSets);
        

        return [$allEpochs, $isRecurring];
}

?>