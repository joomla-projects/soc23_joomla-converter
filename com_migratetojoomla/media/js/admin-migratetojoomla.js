/**
 * @copyright  (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

"use strict";

var data = Joomla.getOptions("com_migratetojoomla.importstring");

var timeperdatabaseprocess = 0;
var ismediamigrate = false;
console.log(data);
function settimer() {
  var totaltask = data.length;

  data.forEach((element) => {
    if (element[1] == "mediadata") {
      ismediamigrate = true;
    }
  });

  var totaldatabasepercentage = 100;
  if (ismediamigrate) {
    // if media migration selected 30% progress correspond to media progress
    totaldatabasepercentage -= 30;
    totaltask -= 1;
  }

  timeperdatabaseprocess = totaldatabasepercentage / totaltask;

  var initialtime = totaldatabasepercentage % totaltask;

  console.log("progress per data : " + timeperdatabaseprocess);
  console.log("total progress bar : " + totaldatabasepercentage);

  document
    .getElementById("migratetojoomlabar")
    .setAttribute("style", `width: ${initialtime}%`);
  document.getElementById("progresspercent").innerHTML = `${initialtime}%`;
}

function addsteps() {
  var data = Joomla.getOptions("com_migratetojoomla.importstring");

  var domelement = document.getElementById("migratetojoomla_listgroup");
  // remove previous steps
  domelement.innerHTML = "";

  const size = data.length;

  for (var index = 0; index < size; index++) {
    var element = data[index];

    var status = element[0];
    var fieldname = element[1];

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

  // console.log("progress :" + step+ prevpercent);
  if (step == "mediadata") {
    prevpercent += 30;
  } else {
    prevpercent += timeperdatabaseprocess;
  }
  console.log("progress :" + step + prevpercent);
  document
    .getElementById("migratetojoomlabar")
    .setAttribute("style", `width: ${prevpercent}%`);
  document.getElementById("progresspercent").innerHTML = `${prevpercent}%`;
}

function endmigration() {
  // hide progress bar
  document.getElementById("migratetojoomla_progress").style.display = "none";

  // hide steps
  document.getElementById("migratetojoomla_listgroup").innerHTML = "";

  // here go ajax request to progress controller Method that will show errors and messages;

  // show log
  document.getElementById("migratetojoomla_log").style.display = "block";
}

const handlemigration = async () => {
  addsteps();

  // display progress bar
  document.getElementById("migratetojoomla_progress").style.display = "block";

  settimer();

  // calling ajax controller with all selected fields

  const size = data.length;

  console.log("progress per data : " + timeperdatabaseprocess);
  // console.log("total progress bar")
  for (var index = 0; index < size; index++) {
    var element = data[index];

    var status = element[0];
    var fieldname = element[1];

    // make ajax request

    const r = await Joomla.request({
      url: `index.php?option=com_migratetojoomla&task=progress.ajax&format=json&name=${fieldname}`,
      method: "POST",
      headers: { "Content-Type": "application/json" },
      onSuccess: (response) => {
        console.log("Success" + JSON.parse(response)[0].name);

        // update current field step as success
        updatesteps("success", JSON.parse(response)[0].name);

        // update progress bar

        updateprogressbar(JSON.parse(response)[0].name);
      },
      onError: (response) => {
        console.log("error" + JSON.parse(response)[0].name);

        // update current field step as error
        updatesteps("fail", index);

        updateprogressbar(JSON.parse(response)[0].name);
      },
    });

    // endmigration
    endmigration();
  }

  // hide start migrate button
  document.getElementById("migratetojoomla_startmigrate").style.display =
    "none";

  // ajax loop over paramters to progress ajax method
};

document.addEventListener("DOMContentLoaded", function () {
  document
    .getElementById("migratetojoomla_startmigrate")
    .addEventListener("click", handlemigration);
});
