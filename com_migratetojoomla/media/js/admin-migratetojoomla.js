/**
 * @copyright  (C) 2018 Open Source Matters, Inc. <https://www.joomla.org>
 * @license    GNU General Public License version 2 or later; see LICENSE.txt
 */

"use strict";

var data = Joomla.getOptions("com_migratetojoomla.importstring");

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
      default:
        fieldclass = "text-dark";
    }

    var child = document.createElement("li");
    child.className = "list-group-item " + `${fieldclass}`;
    child.innerHTML = fieldname.charAt(0).toUpperCase() + fieldname.slice(1);
    domelement.appendChild(child);
  }
}

// function changestepstatus

function makeajax() {
  console.log("ajax function here");
  Joomla.request({
    url: "index.php?option=com_migratetojoomla&view=information&task=ajax",
    method: "POST",
    data,
    onSuccess: (response) => {
      alert("success: ");
    },
    onError: () => {
      alert("There is an error");
    },
  });

}

document.addEventListener("DOMContentLoaded", function () {
  document
    .getElementById("migratetojoomla_startmigrate")
    .addEventListener("click", addsteps);

  document
    .getElementById("migratetojoomla_ajax")
    .addEventListener("click", makeajax);
});
