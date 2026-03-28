import { useCallback, useEffect, useMemo, type MouseEvent } from 'react';
import ReactFlow, {
  Background,
  BackgroundVariant,
  Controls,
  MiniMap,
  addEdge,
  useEdgesState,
  useNodesState,
  useReactFlow,
  type Connection,
  type Edge,
  type Node,
  MarkerType,
} from 'reactflow';
import 'reactflow/dist/style.css';
import { isFlowDomainNode } from '../../../data/flowNodeMap';
import { useAiStudio } from '../../../hooks/useAiStudio';
import { parseStudioFlowJson } from '../../../utils/parseStudioFlow';
import { initialStudioEdges, initialStudioNodes } from './reactFlow/initialFlowState';
import { studioFlowNodeTypes } from './reactFlow/studioNodeTypes';

const defaultEdgeOptions = {
  type: 'smoothstep' as const,
  style: { stroke: '#2563eb', strokeWidth: 2 },
  markerEnd: {
    type: MarkerType.ArrowClosed,
    color: '#2563eb',
    width: 18,
    height: 18,
  },
};

function minimapColor(node: Node): string {
  const accent = (node.data as { accent?: string } | undefined)?.accent;
  if (accent === 'personality') return '#bfdbfe';
  if (accent === 'restrictions') return '#a7f3d0';
  if (accent === 'sales') return '#ddd6fe';
  if (accent === 'qa') return '#fed7aa';
  return '#fff';
}

function StudioFlowSnapshotRegistrar() {
  const { registerFlowSnapshotGetter } = useAiStudio();
  const rf = useReactFlow();

  useEffect(() => {
    const getter = () => {
      try {
        return JSON.stringify(rf.toObject());
      } catch {
        return null;
      }
    };
    registerFlowSnapshotGetter(getter);
    return () => registerFlowSnapshotGetter(null);
  }, [rf, registerFlowSnapshotGetter]);

  return null;
}

function StudioFlowDiagramInner({
  initialNodes,
  initialEdges,
}: {
  initialNodes: Node[];
  initialEdges: Edge[];
}) {
  const { selectFromCanvas } = useAiStudio();
  const [nodes, , onNodesChange] = useNodesState(initialNodes);
  const [edges, setEdges, onEdgesChange] = useEdgesState(initialEdges);

  const onConnect = useCallback(
    (params: Connection) => setEdges((eds) => addEdge({ ...params, ...defaultEdgeOptions }, eds)),
    [setEdges],
  );

  const onNodeClick = useCallback(
    (_: MouseEvent, node: Node) => {
      if (!isFlowDomainNode(node.id)) return;
      selectFromCanvas(node.id);
    },
    [selectFromCanvas],
  );

  const nodeTypes = useMemo(() => studioFlowNodeTypes, []);

  return (
    <ReactFlow
      nodes={nodes}
      edges={edges}
      onNodesChange={onNodesChange}
      onEdgesChange={onEdgesChange}
      onConnect={onConnect}
      onNodeClick={onNodeClick}
      nodeTypes={nodeTypes}
      defaultEdgeOptions={defaultEdgeOptions}
      fitView
      fitViewOptions={{ padding: 0.18, maxZoom: 1.05 }}
      minZoom={0.35}
      maxZoom={1.5}
      zoomOnScroll
      zoomOnDoubleClick={false}
      nodesConnectable
      elementsSelectable
      connectionLineStyle={{ stroke: '#2563eb', strokeWidth: 2 }}
      deleteKeyCode={['Backspace', 'Delete']}
    >
      <StudioFlowSnapshotRegistrar />
      <Background id="studio-flow-bg" variant={BackgroundVariant.Dots} gap={14} size={1} color="#cbd5e1" />
      <Controls className="studio-react-flow-controls" showInteractive={false} />
      <MiniMap
        className="studio-react-flow-minimap"
        pannable
        zoomable
        maskColor="rgba(243, 244, 246, 0.85)"
        nodeStrokeWidth={2}
        nodeColor={(n) => minimapColor(n)}
      />
    </ReactFlow>
  );
}

export function StudioFlowDiagram() {
  const { behaviourDraft, flowCanvasKey } = useAiStudio();
  const { nodes: initialNodes, edges: initialEdges } = useMemo(
    () => parseStudioFlowJson(behaviourDraft.flow_graph_json, initialStudioNodes, initialStudioEdges),
    [behaviourDraft.flow_graph_json],
  );

  return (
    <div className="studio-react-flow" key={flowCanvasKey}>
      <StudioFlowDiagramInner initialNodes={initialNodes} initialEdges={initialEdges} />
    </div>
  );
}
