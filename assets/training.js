
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
    let powerPhase = Math.round((phase.value*myTrainingFTP) / 5) * 5;
    data.push([phase.start, powerPhase]);
  }

  console.log("data : " + data);

  trainingGraph = data;

  updateCharts();

}

