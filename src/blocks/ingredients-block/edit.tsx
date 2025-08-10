import { useBlockProps } from '@wordpress/block-editor';
import EditSelectedBlock from './EditSelectedBlock';
import BlockPreview from './BlockPreview';

export default function Edit( props ) {
	const { attributes, isSelected } = props;
	const blockProps = useBlockProps( {
		style: {
			display: 'flex',
			gap: '1rem',
			flexWrap: 'wrap',
			alignItems: 'center',
		},
	} );

	return (
		<li { ...blockProps }>
			{ isSelected ? (
				<EditSelectedBlock { ...props } />
			) : (
				<BlockPreview attributes={ attributes } />
			) }
		</li>
	);
}
