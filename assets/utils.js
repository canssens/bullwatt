function myUUID() {
  myUUID = localStorage.myuuid;
  if(myUUID == null || myUUID == "")
  {
    myUUID = "10000000-1000-4000-8000-100000000000".replace(/[018]/g, c =>
      (+c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> +c / 4).toString(16)
    );
    localStorage.myuuid = myUUID;
  }
  // Cookie
  const expirationDate = new Date();
  expirationDate.setFullYear(expirationDate.getFullYear() + 100);
  document.cookie = "userBullWatt="+myUUID+";expires="+expirationDate.toUTCString()+";path=/";
}

myUUID();

// retun the unix timestamp of activities stored in locale storage
function getActivitiesDate() {
      let activitiesDate = [];
      let activities = null;
      let activitiesJson = localStorage.activities;
      if(activitiesJson != null && activitiesJson != "")
      {
        activities = JSON.parse(activitiesJson);

        activities["activities"].forEach(element => {
          let dateactivity = Date.parse(element.startTime);
          activitiesDate.push(dateactivity);
          
          
          //purge activity timeseries older than 1 month
          let now = new Date();
          let oneMonthAgo = new Date(now.getFullYear(), now.getMonth() - 1, now.getDate());
          if(dateactivity < oneMonthAgo.getTime())
          {
            element.timeseries = [];
          }

        });

        // save the activities back to local storage
        localStorage.activities = JSON.stringify(activities);
      }
      return activitiesDate;
    }


function checkConnectionStrava()
{
  console.log('Checking Strava connection status...');
  let url = 'strava.php';
  const xhr = new XMLHttpRequest();
  xhr.open('GET', url);

  xhr.onload = function() {
      if (xhr.status >= 200 && xhr.status < 300) {
          console.log('Reponse:', JSON.parse(xhr.responseText));
          let obj = JSON.parse(xhr.responseText);
          console.log(obj);
          if(obj["message"]=="logged")
          {
            console.log("logged");
            document.getElementById("stravaLink").innerHTML = "logout Strava";
            document.getElementById("stravaLink").href = "strava.php?logout=true";
          }

          //manage the athlete data : weight
          if(obj["athlete.weight"] != null && obj["athlete.weight"] != "")
          {
            //persist in local storage the weight obj["athlete.weight"];
            localStorage.athleteWeight = obj["athlete.weight"];
          }
          

      } else {
          console.error('Error:', xhr.status, xhr.statusText);
      }
  };

  xhr.onerror = function() {
      console.error('Error strava connection');
  };

  xhr.send();
}

function pushTechnicalLog(messageLog)
{
  let url = 'technical_log.php';
  const xhr = new XMLHttpRequest();

  xhr.open("POST", url, true);

  // Send the proper header information along with the request
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");

  xhr.onreadystatechange = () => {
    // Call a function when the state changes.
    if (xhr.readyState === XMLHttpRequest.DONE && xhr.status === 200) {
      // Request finished. Do processing here.
    }
  };

  if(samplingLog == 0)
  {
    samplingLog = -1;
    xhr.send("done=1&uuid="+myUUID+"&log="+encodeURIComponent(messageLog));
  }
  else 
  {
    xhr.send("uuid="+myUUID+"&log="+encodeURIComponent(messageLog));
    }
}


function updateSliderValue(value) {
  const valueDisplay = document.getElementById("sliderValue");
  valueDisplay.innerHTML = value;
  // save locally the FTP value
  localStorage.myFTP = value;
}

function getFTP() {
  let ftp = parseInt(localStorage.myFTP);
  if(typeof ftp === 'undefined' || ftp == null || ftp == "" || ftp == 0 || isNaN(ftp))
  {
    ftp = 200;
  }
  return ftp;
}



