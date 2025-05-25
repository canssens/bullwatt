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
        });
      }
      return activitiesDate;
    }


function checkConnectionStrava()
{
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
      } else {
          console.error('Erreur:', xhr.status, xhr.statusText);
      }
  };

  xhr.onerror = function() {
      console.error('Erreur de reseau');
  };

  xhr.send();
}


function updateSliderValue(value) {
  const valueDisplay = document.getElementById("sliderValue");
  valueDisplay.innerHTML = value;
  // save locally the FTP value
  localStorage.myFTP = value;
}

function getFTP() {
  let ftp = parseInt(localStorage.myFTP);
  if(typeof ftp === 'undefined' || ftp == null || ftp == "" || ftp == 0)
  {
    ftp = 200;
  }
  return ftp;
}



