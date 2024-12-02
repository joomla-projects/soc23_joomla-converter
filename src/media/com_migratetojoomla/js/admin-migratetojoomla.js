/**
 * @copyright  (C) 2024 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

"use strict";

var data = Joomla.getOptions("com_migratetojoomla.importstring");
var displaystring = Object.values(
  Joomla.getOptions("com_migratetojoomla.displayimportstring")
);

var tablekeys = Joomla.getOptions("com_migratetojoomla.keys");

var timeperdatabaseprocess = 0;
var mediaprocess = 0;
var ismediamigrate = false;

// remove unnecessary things from data
var temp = [];
for (var index = 0; index < data.length; index++) {
  if (data[index][1].indexOf("data") != -1) {
    temp.push(data[index]);
  }
}

data = temp;

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
    keysarray.forEach(function (element) {
      totalkeys += element.length;
    });
    timeperdatabaseprocess = parseInt(totaldatabasepercentage / totalkeys);
  }

  var initialtime = 0;
  if (totaltask) {
    initialtime = totaldatabasepercentage % totaltask;
  }

  if (totalkeys) {
    initialtime = totaldatabasepercentage % totalkeys;
  }

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

// update state of steps
function updatesteps(response, fieldname) {
  const size = data.length;

  for (var index = 0; index < size; index++) {
    if (fieldname == data[index][1]) {
      data[index][0] = response;
      break;
    }
  }
  addsteps();
}

function updateprogressbar(step) {
  var prevpercent = document.getElementById("progresspercent").innerHTML;
  prevpercent = parseInt(prevpercent.slice(0, 2));
  if (step == "mediadata") {
    prevpercent += mediaprocess;
  } else {
    prevpercent += timeperdatabaseprocess;
  }
  document
    .getElementById("migratetojoomlabar")
    .setAttribute("style", `width: ${prevpercent}%`);
  document.getElementById("progresspercent").innerHTML = `${prevpercent}%`;
}

function endmigration() {
  // hide progress bar
  document.getElementById("migratetojoomla_progressbar").style.display = "none";

  // hide steps
  document.getElementById("migratetojoomla_listgroup").innerHTML = "";

  // here go ajax request to progress controller Method that will show errors and messages;

  // show log
  document.getElementById("migratetojoomla_log").style.display = "block";

  document.getElementById("migratetojoomla_progress").style.display = "none";
}

function updateprogress(status, message, id, text = status) {
  var displaytext = `${message} with id ${id} Imported Successfully`;
  if (message == "media") {
    displaytext = "Media Imported Successfully";
  }
  if (message == "start") {
    displaytext = "start";
  }
  document.getElementById("migratetojoomla_progressstatus").innerHTML = text;
  document.getElementById(
    "migratetojoomla_progressstatus"
  ).className = `p-2 mb-2 bg-${status} text-white text-center`;
  document.getElementById("migratetojoomla_progresstext").innerHTML =
    displaytext;
}

const handlemigration = async () => {
  document.getElementById("migratetojoomla_startmigrate").style.display =
    "none";
  settimer();
  document.getElementById("migratetojoomla_progressbar").style.display =
    "block";
  document.getElementById("migratetojoomla_progress").style.display = "block";
  addsteps();
  updateprogress("primary", "start", 0, "importing..");

  // for mediamigration
  if (ismediamigrate) {
    const response = await ajaxRequest(0, "media");
  }
  const allFields = Object.keys(tablekeys);

  const noOfFields = allFields.length;
  for (var index = 0; index < noOfFields; index++) {
    var keys = tablekeys[allFields[index]];
    const noOfKeys = keys.length;
    updatesteps("active", allFields[index] + "data");
    for (var key = 0; key < noOfKeys; key++) {
      const response = await ajaxRequest(keys[key], allFields[index]);
    }
    updatesteps("success", allFields[index] + "data");
  }
  const response = await ajaxRequest(0, "end");
  endmigration();
};

async function ajaxRequest(key, fieldname) {
  return new Promise((resolve, reject) => {
    Joomla.request({
      url: `index.php?option=com_migratetojoomla&task=progress.ajax&format=json&name=${fieldname}&key=${key}`,
      method: "POST",
      headers: { "Content-Type": "application/json" },
      onSuccess: (response) => {
        const result = JSON.parse(response);
        // update progress bar

        updateprogressbar(fieldname);
        const progresstatus =
          result["status"] == "error" ? "danger" : "success";
        updateprogress(progresstatus, fieldname, key);
        resolve();
      },
      onError: (error) => {
        updateprogressbar(fieldname);
        updateprogress("danger", fieldname, key);
        reject();
      },
    });
  }).catch((error) => {
    updateprogress("danger", fieldname, key);
  });
}

document.addEventListener("DOMContentLoaded", function () {
  document
    .getElementById("migratetojoomla_startmigrate")
    .addEventListener("click", handlemigration);
});
