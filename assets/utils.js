function myUUID() {
  myUUID = localStorage.myuuid;
  if(myUUID == null || myUUID == "")
  {
    myUUID = "10000000-1000-4000-8000-100000000000".replace(/[018]/g, c =>
      (+c ^ crypto.getRandomValues(new Uint8Array(1))[0] & 15 >> +c / 4).toString(16)
    );
    localStorage.myuuid = myUUID;
  }
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
  
