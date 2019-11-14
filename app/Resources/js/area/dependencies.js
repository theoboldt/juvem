$(function () {
    const el = $('#vis-network');
    if (!el.length) {
        return;
    }
    el.on('resize', function () {
        el.css('height', window.innerHeight - 75 + 'px');
    }());

    el.find('span').text('Die Knoten werden geknüpft...');

    $.ajax({
        type: 'GET',
        url: 'dependencies/data.json',
        success: function (response) {
            el.html('');
            el.removeClass('loading');
            if (response.nodes && response.edges && response.participationEdges) {
                render(response.nodes, response.edges, response.participationEdges);
            } else {
                $(document).trigger('add-alerts', {
                    message: 'Unerwartetes Datenformat der Beziehungsdaten',
                    priority: 'error'
                });
            }
        },
        error: function () {
            $(document).trigger('add-alerts', {
                message: 'Die Beziehungsdaten konnten nicht geladen werden',
                priority: 'error'
            });
        }
    });

    render = function (nodes, edges, participationEdges) {
        var filterGroups = null,
            filterShowParticipants = null,
            filterShowParticipationEdges = null,
            filterIncludeChoices = [],
            container = document.getElementById('vis-network'),
            edgesSet = new vis.DataSet(edges),
            nodesSet = new vis.DataSet(nodes),
            nodesView = new vis.DataView(nodesSet, {
                filter: function (item) {
                    if (filterGroups === null) {
                        return true;
                    }
                    var type = item.type;

                    if (!filterShowParticipants && type === 'participant') {
                        return false;
                    }
                    if (type === 'choice' && $.inArray(item.bid, filterIncludeChoices) === -1) {
                        return false;
                    }

                    var acceptGroup = false;
                    $.each(filterGroups, function (groupName, acceptedValues) {
                        var groupValue = item[groupName];
                        acceptGroup = false;

                        $.each(acceptedValues, function (key, acceptedConfiguration) {
                            var acceptedValue = acceptedConfiguration.value,
                                filterableNodes = acceptedConfiguration.nodes;

                            if ($.inArray(type, filterableNodes) === -1
                                || groupValue === acceptedValue) {
                                acceptGroup = true;
                                return false;
                            }
                        });
                        if (!acceptGroup) {
                            return false;
                        }
                    });

                    return acceptGroup;
                }
            });

        const hideParticipationEdges = function () {
                var edges = edgesSet.get({
                    filter: function (item) {
                        return (item.type === 'participation');
                    }
                });
                $.each(edges, function (key, edge) {
                    edgesSet.remove(edge.id);
                });
            },
            showParticipationEdges = function () {
                hideParticipationEdges();
                $.each(participationEdges, function (key, edge) {
                    edgesSet.add(edge);
                });
            };
        showParticipationEdges();

        const connectToChoice = function (nodeHuman, nodeChoice) {
            var nodeHumanId = nodeHuman.id,
                nodeChoiceId = nodeChoice.id,
                newBid = nodeChoice.bid,
                connectedNodes = network.getConnectedNodes(nodeHumanId);

            if ($.inArray(nodeChoiceId, connectedNodes) !== -1) {
                return false; //already part of this group
            }

            //remove other connections to same bid
            var connectedEdges = network.getConnectedEdges(nodeHumanId);
            $.each(connectedEdges, function (key, connectedEdgeId) {
                var connectedEdge = edgesSet.get(connectedEdgeId);
                if (connectedEdge.bid === newBid) {
                    edgesSet.remove(connectedEdgeId);
                }
            });

            var newEdges = edgesSet.add({
                from: nodeHumanId,
                to: nodeChoiceId,
                bid: newBid,
                choiceId: nodeChoice.choiceId,
                color: {color: nodeChoice.color},
                dashes: true
            });
            if (!newEdges) {
                debugger
            }
            nodesView.refresh();
            $.ajax({
                type: 'POST',
                url: '/admin/event/' + el.data('eid') + '/dependencies/change_group_assignment/' + nodeHumanId,
                data: {
                    _token: el.data('token'),
                    bid: newBid,
                    choiceId: nodeChoice.choiceId
                },
                success: function (response) {
                    if (response.success) {
                        edgesSet.update({id: newEdges[0], dashes: false});
                    } else {
                        location.reload();
                    }
                },
                error: function () {
                    location.reload();
                }
            });
            network.addEdgeMode();
            updateGroupTitles();
        };

        var options = {
            //layout: {improvedLayout: false},
            manipulation: {
                enabled: false,
                addEdge: function (edgeData, callback) {
                    if (edgeData.from === edgeData.to) {
                        return; //not adding self connections
                    } else {
                        var nodeFrom = nodesSet.get(edgeData.from),
                            nodeFromType = nodeFrom.type,
                            nodeTo = nodesSet.get(edgeData.to),
                            nodeToType = nodeTo.type;

                        if ((nodeFromType !== 'choice' && nodeToType !== 'choice')
                            || (nodeFromType === 'choice' && nodeToType === 'choice')
                        ) {
                            return; //only able to connect choices but not only choices
                        }
                        if (nodeFromType === 'choice') {
                            //swap nodes in order to ensure connection has correct direction
                            var nodeTmp = nodeFrom;
                            nodeFrom = nodeTo;
                            nodeTo = nodeTmp;
                        }

                        connectToChoice(nodeFrom, nodeTo, callback);
                    }
                }
            },
            nodes: {
                font: {
                    face: '"Helvetica Neue", Helvetica, Arial, sans-serif',
                    multi: 'md'
                },
                scaling: {
                    min: 20,
                    max: 60
                }
            },
            edges: {
                font: {
                    face: '"Helvetica Neue", Helvetica, Arial, sans-serif',
                    multi: 'html'
                },
                scaling: {
                    min: 0.5,
                    max: 5
                }
            }
        };

        const formatNumber = function (number) {
            number = (Math.round(number * 10) / 10);
            var stringNumber = number.toString();
            return stringNumber.replace('.', ',');
        };

        var network = new vis.Network(
            container,
            {
                nodes: nodesView,
                edges: edgesSet
            },
            options
        );
        const updateGroupTitles = function () {
            var nodeUpdates = [],
                edgeUpdates = [];

            nodesSet.forEach(function (node) {
                if (node.type === 'choice') {
                    var connectedNodeIds = network.getConnectedNodes(node.id),
                        groupAges = [],
                        label,
                        median,
                        mean,
                        range,
                        distance,
                        nodeSize = (connectedNodeIds.length * 1.5) + 20;
                    $.each(connectedNodeIds, function (key, connectedNodeId) {
                        var connectedNode = nodesSet.get(connectedNodeId);
                        if (connectedNode.type === 'participant') {
                            if (connectedNode.age) {
                                groupAges.push(connectedNode.age);
                            }
                        }
                    });

                    if (node.shortTitle) {
                        label = '*' + node.shortTitle + '*';
                    }
                    if (groupAges.length) {
                        label += "\n";
                        median = eMedian(groupAges);
                        mean = eMean(groupAges);
                        distance = (Math.max(...groupAges) - Math.min(...groupAges));

                        label += ' ~' + formatNumber(median);
                        label += ' x' + formatNumber(mean);
                        label += ' d' + formatNumber(distance);

                        if (node.label === label
                            && node.median === median
                            && node.mean === mean
                            && node.distance === distance
                            && node.nodeSize === nodeSize) {
                            return true;
                        }
                        nodeUpdates.push({
                            id: node.id,
                            label: label,
                            median: median,
                            mean: mean,
                            distance: distance,
                            value: nodeSize
                        });
                        var connectedEdges = network.getConnectedEdges(node.id);
                        $.each(connectedEdges, function (key, connectedEdgeId) {
                            var connectedEdge = edgesSet.get(connectedEdgeId),
                                connectedNode = nodesSet.get(connectedEdge.from),
                                edgeDistance;
                            if (!connectedNode || connectedNode.type !== 'participant' || connectedEdge.type !== 'choice') {
                                return;
                            }
                            edgeDistance = (connectedNode.age - mean) + 0.25;
                            if (edgeDistance < 0) {
                                edgeDistance *= -1;
                            }
                            edgeUpdates.push({
                                id: connectedEdgeId,
                                value: edgeDistance
                            });
                        });
                    } else if (node.label !== label) {
                        nodeUpdates.push({
                            id: node.id,
                            label: label,
                            median: 0,
                            mean: 0,
                            distance: 0,
                            value: nodeSize
                        });
                    }
                }
            });

            var handleQueue = function () {
                var update;
                if (nodeUpdates.length) {
                    update = nodeUpdates.shift();
                    nodesSet.update(update);
                }
                if (edgeUpdates.length) {
                    update = edgeUpdates.shift();
                    edgesSet.update(update);
                }
                scheduleQueue();
            }, scheduleQueue = function () {
                if (nodeUpdates.length || edgeUpdates.length) {
                    window.setTimeout(handleQueue, 1);
                }
            };
            scheduleQueue();
        };

        network.on("doubleClick", function (params) {
            if (params.nodes.length !== 1) {
                return;
            }
            var nodeId = params.nodes[0];
            if (network.isCluster(nodeId)) {
                network.openCluster(nodeId);
            } else {
                var node = nodesSet.get(nodeId);

                switch (node.type) {
                    case 'choice':
                        var clusterOptionsByData = {
                            processProperties: function (clusterOptions, childNodes) {
                                clusterOptions.label = node.label + ' [' + (childNodes.length - 1) + ']';
                                clusterOptions.color = node.color;
                                clusterOptions.shape = node.shape;
                                return clusterOptions;
                            },
                            clusterNodeProperties: {
                                borderWidth: 2,
                                shapeProperties: {borderDashes: [5, 2]}
                            }
                        };
                        network.clusterByConnection(nodeId, clusterOptionsByData);
                        break;
                    case 'participant':
                        $('#modalParticipant' + node.aid).modal('show');
                        break;
                }

            }
        });
        network.on("selectNode", function (params) {
            if (params.nodes.length !== 1) {
                return;
            }
            var nodeId = params.nodes[0],
                node = nodesSet.get(nodeId);
        });
        $('.modes label').on('change', function () {
            var el = $(this),
                elInput = el.find('input'),
                mode = elInput.attr('id');

            if (mode === 'add-edge') {
                network.addEdgeMode();
            } else {
                network.disableEditMode();
            }
        });

        const filtersInputEls = $('.filters input'),
            filterIncludeChoicesEls = $('.filter-entities input.f'),
            updateFilterConfiguration = function () {
                filterGroups = {};
                filtersInputEls.each(function () {
                    var filterEl = $(this);
                    if (filterEl.prop('checked')) {
                        var property = filterEl.data('property'),
                            value = filterEl.data('value'),
                            nodes = filterEl.data('nodes');
                        if (!filterGroups[property]) {
                            filterGroups[property] = [];
                        }
                        filterGroups[property].push({
                            value: value,
                            nodes: nodes.split(',')
                        });
                    }
                });
                filterShowParticipants = false;
                filterShowParticipationEdges = false;
                filterIncludeChoices = [];
                filterIncludeChoicesEls.each(function () {
                    var filterEl = $(this);
                    if (filterEl.prop('checked')) {
                        var type = filterEl.data('type'),
                            bid = filterEl.data('bid');

                        switch (type) {
                            case 'participation-edges':
                                filterShowParticipationEdges = true;
                                break;
                            case 'participant':
                                filterShowParticipants = true;
                                break;
                            case 'choice':
                                filterIncludeChoices.push(bid);
                                break;
                        }
                    }
                });
                nodesView.refresh();
                $('#display').click();
            };
        filtersInputEls.on('change', updateFilterConfiguration);
        filterIncludeChoicesEls.on('change', updateFilterConfiguration);

        $('#btnShowParticipationEdges').on('change', function () {
            if ($(this).prop('checked')) {
                showParticipationEdges();
            } else {
                hideParticipationEdges();
            }
        });

        updateGroupTitles();
        nodesView.refresh();
    };
});