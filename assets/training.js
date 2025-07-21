
function loadTrainingData() {
  // load Json file data
  fetch('./trainings.json')
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      // Process the JSON data here      
      // Find the training session with the given ID
      const trainingSessionData = data.find(training => training.id === trainingId);
      trainingContent = trainingSessionData;
      calculateGraph();
      
    })
    .catch(error => {
      console.error('There was a problem with the fetch operation:', error);
    });
}


function getParameters() {
  const queryString = window.location.search;
    // Création d'un objet URLSearchParams pour manipuler facilement les paramètres
    const params = new URLSearchParams(queryString);
    // Récupération des valeurs des paramètres
    trainingId = params.get('training');
    if(trainingId !== null){
      trainingSession = true;
      console.log("training session : " + trainingId);
      loadTrainingData();
      
    }
}


function calculateGraph()
{

  data = [];

  // from trainingContent, get the data from phases
  // for each phase, get the data from the graph
  for (let i = 0; i < trainingContent.phases.length; i++) {
    const phase = trainingContent.phases[i];
    let powerPhase = Math.round((phase.value * myTrainingFTP ) / 5) * 5;
    data.push([phase.start, powerPhase]);
  }

  console.log("data : " + data);

  trainingGraph = data;

  updateCharts();

}


function mergeArraysByDuration(array1, array2, mergeDuration) {
  const mergedArray = [];

  // Add elements from array1 up to the mergeDuration
  for (const element of array1) {
    const [duration, value] = element;
    if (duration <= mergeDuration) {
      mergedArray.push(element);
    } else {
      // Assuming arrays are sorted by duration, we can stop early
      break;
    }
  }

  // Add elements from array2 after the mergeDuration
  for (const element of array2) {
    const [duration, value] = element;
    if (duration > mergeDuration) {
      mergedArray.push(element);
    }
  }

  // Note: This assumes the input arrays are sorted by duration.
  // If they are not sorted, you would need to sort them first or
  // adjust the logic to iterate through both arrays completely
  // and check the duration condition for each element.

  return mergedArray;
}

