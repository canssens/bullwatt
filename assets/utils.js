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




function checkConnectionStrava()
{
  console.log('Checking Strava connection status...');
  let url = './service/strava.php';
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
            document.getElementById("stravaLink").href = "/service/strava.php?logout=true";
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
  let url = './service/technical_log.php';
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

// --- Unit system detection and management (miles vs metric) ---

// Locales that prefer miles: US, UK, Myanmar (MM), Liberia (LR)
const MILES_COUNTRY_CODES = ['US', 'GB', 'MM', 'LR'];

/**
 * Detect the user's locale and set the unit system preference in localStorage.
 * Only sets the value if it has not been set before (first visit).
 * Returns the current unit system: 'miles' or 'metric'.
 */
function detectAndSetUnitSystem() {
  // If already set by the user, keep their choice
  if (localStorage.unitSystem === 'miles' || localStorage.unitSystem === 'metric') {
    return localStorage.unitSystem;
  }

  let prefersMiles = false;

  try {
    // navigator.languages gives an array like ["en-US", "en"], navigator.language gives "en-US"
    const locales = navigator.languages && navigator.languages.length
      ? navigator.languages
      : [navigator.language || navigator.userLanguage || 'en'];

    // Check the primary locale for a country code suffix (e.g. "en-US" -> "US")
    for (const locale of locales) {
      const parts = locale.split('-');
      if (parts.length >= 2) {
        const country = parts[parts.length - 1].toUpperCase();
        if (MILES_COUNTRY_CODES.includes(country)) {
          prefersMiles = true;
        }
        break; // Only check the first locale that has a country code
      }
    }
  } catch (e) {
    console.warn('Could not detect locale for unit system:', e);
  }

  const unit = prefersMiles ? 'miles' : 'metric';
  localStorage.unitSystem = unit;
  return unit;
}

/**
 * Get the current unit system preference.
 * @returns {'miles'|'metric'}
 */
function getUnitSystem() {
  return localStorage.unitSystem === 'miles' ? 'miles' : 'metric';
}

/**
 * Manually set the unit system preference.
 * @param {'miles'|'metric'} unit
 */
function setUnitSystem(unit) {
  localStorage.unitSystem = (unit === 'miles') ? 'miles' : 'metric';
}

/**
 * Convenience check: returns true if current unit system is miles.
 * @returns {boolean}
 */
function isMiles() {
  return getUnitSystem() === 'miles';
}



