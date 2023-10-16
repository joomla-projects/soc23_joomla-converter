/**
 * @copyright  (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

"use strict";

var data = Joomla.getOptions("com_migratetojoomla.importstring");
var displaystring = Object.values(
  Joomla.getOptions("com_migratetojoomla.displayimportstring")
);

var tablekeys = Joomla.getOptions("com_migratetojoomla.keys");

console.log("tablekeys");
console.log(tablekeys);
var timeperdatabaseprocess = 0;
var mediaprocess = 0;
var ismediamigrate = false;

console.log("display string information");
console.log(displaystring);
console.log("data");
console.log(data);

var arrayimportstring = Joomla.getOptions("com_migratetojoomla.arrayimportstring");
console.log("Array Import string");
console.log(arrayimportstring);

function settimer() {
  var totaltask = data.length;
  data.forEach((element) => {
    if (element[1] == "mediadata") {
      ismediamigrate = true;
    }
  });

  var totaldatabasepercentage = 100;
  if (ismediamigrate) {
    // if media migration selected mediaprocess% progress correspond to media progress
    mediaprocess = mediaprocess;
    totaltask -= 1;
    if (data.length == 1) {
      mediaprocess = 100;
    }
  }

  var totalkeys = 0;
  if (totaltask) {
    const keysarray = Object.values(tablekeys);
    keysarray.forEach(function(element) {
      totalkeys +=element.length;
    });
    timeperdatabaseprocess = parseInt(totaldatabasepercentage / totalkeys);
  }

  var initialtime = 0;
  if (totaltask) {
    initialtime = totaldatabasepercentage % totaltask;
  }

  if(totalkeys) {
    initialtime = totaldatabasepercentage % totalkeys;
  }
  console.log("progress per data : " + timeperdatabaseprocess);
  console.log("total progress bar : " + totaldatabasepercentage);

  document
    .getElementById("migratetojoomlabar")
    .setAttribute("style", `width: ${initialtime}%`);
  document.getElementById("progresspercent").innerHTML = `${initialtime}%`;
}

function addsteps() {
  var domelement = document.getElementById("migratetojoomla_listgroup");
  // remove previous steps
  domelement.innerHTML = "";

  const size = data.length;

  for (var index = 0; index < size; index++) {
    var element = data[index];

    var status = element[0];
    var fieldname = element[1];

    // if (displaystring.includes(fieldname)) {
      var fieldclass = "";

      switch (status) {
        case "active":
          fieldclass = "bg-primary text-white";
          break;
        case "success":
          fieldclass = "bg-success text-white";
          break;
        case "fail":
          fieldclass = "bg-danger text-white";
          break;
        default:
          fieldclass = "text-dark";
      }

      var child = document.createElement("li");
      child.className = "list-group-item " + `${fieldclass}`;
      child.innerHTML = fieldname.charAt(0).toUpperCase() + fieldname.slice(1);
      domelement.appendChild(child);
    }
  }
// }

// update state of steps
function updatesteps(response, fieldname) {
  const size = data.length;

  for (var index = 0; index < size; index++) {
    if (fieldname == data[index][1]) {
      console.log("update step" + data[index][1]);
      data[index][0] = response;
      break;
    }
  }
  addsteps();
}

function updateprogressbar(step) {
  var prevpercent = document.getElementById("progresspercent").innerHTML;
  prevpercent = parseInt(prevpercent.slice(0, 2));

  console.log("progress :" + step+ prevpercent);
  if (step == "mediadata") {
    prevpercent += mediaprocess;
  } else {
    prevpercent += timeperdatabaseprocess;
  }
  console.log("progress :" + prevpercent);
  document
    .getElementById("migratetojoomlabar")
    .setAttribute("style", `width: ${prevpercent}%`);
  document.getElementById("progresspercent").innerHTML = `${prevpercent}%`;
}

function endmigration() {
  console.log("endmigration call successfully");
  // hide progress bar
  document.getElementById("migratetojoomla_progressbar").style.display = "none";

  // hide steps
  document.getElementById("migratetojoomla_listgroup").innerHTML = "";

  // here go ajax request to progress controller Method that will show errors and messages;

  // show log
  document.getElementById("migratetojoomla_log").style.display = "block";
}

function updateprogress(status , message , id ,text = status) {
//   const element = document.getElementById('myElement');

// // Get the current classes
// const currentClasses = element.classList;

// // Add a new class
// currentClasses.add('newClass');
  var displaytext = `${message} with id ${id} Imported Successfully`;
  if(message=="media"){
    displaytext = "Media Imported Successfully";
  }
  if(message =="start"){
    displaytext = "start";
  }
  document.getElementById("migratetojoomla_progressstatus").innerHTML = text;
  document.getElementById("migratetojoomla_progressstatus").className=`p-2 mb-2 bg-${status} text-white text-center`;
  document.getElementById("migratetojoomla_progresstext").innerHTML = displaytext;
}

// const handlemigration = async () => {
//   document.getElementById("migratetojoomla_startmigrate").style.display =
//     "none";
//   settimer()
//   document.getElementById("migratetojoomla_progressbar").style.display = "block";
//   addsteps();
//   const size = data.length;
//   updateprogress('primary' , "start" , 'importing..');
//   for (var index = 0; index < size; index++) {
//     console.log("current element");
//     console.log(data[index]);
//     var status = data[index][0];
//     var fieldname = data[index][1];
//     console.log(data[index][1]);
//     const response = await ajaxRequest(status, fieldname);

//     console.log("response");
//     console.log(response);
//   }

//   endmigration();
// };

const handlemigration = async ()=>{
    document.getElementById("migratetojoomla_startmigrate").style.display ="none";
    settimer()
    document.getElementById("migratetojoomla_progressbar").style.display = "block";
    addsteps();
    updateprogress('primary' , "start" , 0 , 'importing..');

    // for mediamigration
    if(ismediamigrate) {
      const response = await ajaxRequest(0, "media");
    }
    const allFields = Object.keys(tablekeys);

    const noOfFields  = allFields.length;
    for(var index = 0;index<noOfFields;index++){
        var keys = tablekeys[allFields[index]];
        const noOfKeys = keys.length;
        for(var key =0;key<noOfKeys;key++){
            const response = await ajaxRequest(keys[key], allFields[index]);
        };
    }
    const response = await ajaxRequest(0 ,"end");
    endmigration();
}

function handleError() {
  console.log("error occure");
}

async function ajaxRequest(key, fieldname) {
  return new Promise((resolve, reject) => {
    // request.then(data => {
      updatesteps("active", fieldname);
    Joomla.request({
      url: `index.php?option=com_migratetojoomla&task=progress.ajax&format=json&name=${fieldname}&key=${key}`,
      method: "POST",
      headers: { "Content-Type": "application/json" },
      onSuccess: (response) => {
        // update current field step as success
        updatesteps("success", fieldname);
        console.log("it is a success");
        // console.log(JSON.parse(response));

        // update progress bar        
        updateprogressbar(fieldname);
        updateprogress('success' , fieldname , key);
        resolve();
      },
      onError: (error) => {
        // update current field step as success
        updatesteps("fail", fieldname);
        console.log("it is a error");
        // console.log(JSON.parse(error));
        updatesteps("danger", fieldname);

        // update progress bar

        updateprogressbar(fieldname);
        reject(error);
      },
    });
  }).catch((error) => {
    console.error(`Error in request`);
    reject(error);
  });
}
// return new Promise((resolve, reject) => {
//   const url = new URL(
//     `index.php?option=com_migratetojoomla&task=progress.ajax&format=json&name=${fieldname}`
//   );

// Joomla.request({
//   url: url.toString(),
//   method: 'GET',
//   headers: { 'Content-Type': 'application/json' },
//   onSuccess: (response) => {
//     resolve(normalizeArray(JSON.parse(response).data));
//   },
//   onError: (xhr) => {
//     reject(xhr);
//   },
// });
// Joomla.request({
//   url: `index.php?option=com_migratetojoomla&task=progress.ajax&format=json&name=${fieldname}`,
//   method: "POST",
//   data: JSON.stringify(data),
//   headers: { "Content-Type": "application/json" },
//   onSuccess: (response) => {
//     // update current field step as success
//     updatesteps("success", fieldname);
//     console.log("it is a success");
//     console.log(JSON.parse(response));
//     // update progress bar

//     updateprogressbar(fieldname);
//     resolve();
//   },
//   onError: (error) => {
//     // update current field step as success
//     updatesteps("fail", fieldname);
//     console.log("it is a error");
//     console.log(JSON.parse(error));

//     // update progress bar

//     updateprogressbar(fieldname);
//     reject(error);
//   },
// });
// }).catch(handleError);
// }

document.addEventListener("DOMContentLoaded", function () {
  document
    .getElementById("migratetojoomla_startmigrate")
    .addEventListener("click", handlemigration);
});
